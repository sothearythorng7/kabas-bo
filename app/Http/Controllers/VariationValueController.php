<?php

namespace App\Http\Controllers;

use App\Models\VariationValue;
use App\Models\VariationType;
use App\Models\ProductVariationAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariationValueController extends Controller
{
    /** Regex per known variation type. A value matching the pattern is "clean". */
    private const TYPE_PATTERNS = [
        'size'     => '/^(?:XXS|XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|SM|MEDIUM|LARGE|SMALL|BIG|UNI|UNIQUE|TU|ONE\s?SIZE|A[3-7]|\d{2}|\d+(?:\.\d+)?\s?(?:CM|MM|M|IN))$/i',
        'color'    => '/^[A-Za-z][A-Za-z\s\-&\(\)\.\']*$/u',
        'weight'   => '/^\d+(?:[.,]\d+)?\s?(?:g|kg|mg|gr|gram(?:s)?|gramme(?:s)?)$/i',
        'capacity' => '/^\d+(?:[.,]\d+)?\s?(?:cl|ml|l|liter(?:s)?|litre(?:s)?)$/i',
    ];

    /** A color value must NOT contain these tokens (prevents "50G", "MONDULKIRI PEPPER 50G", etc.) */
    private const COLOR_FORBIDDEN = '/\b(?:\d+(?:\.\d+)?\s?(?:g|kg|mg|ml|cl|l|cm|mm)|XS|S|M|L|XL|XXL|2XL|3XL|SHIRT|PEPPER|CHOCOLAT)\b/i';

    public function index(Request $request)
    {
        $typeId = $request->input('type_id');

        $values = VariationValue::with('type')
            ->withCount('attributes as products_count')
            ->when($typeId, fn($q) => $q->where('variation_type_id', $typeId))
            ->orderBy('variation_type_id')
            ->orderBy('value')
            ->paginate(15)
            ->withQueryString();

        $types = VariationType::orderBy('name')->get();

        return view('variation_values.index', compact('values', 'types', 'typeId'));
    }

    public function create()
    {
        $types = VariationType::all();
        return view('variation_values.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'variation_type_id' => 'required|exists:variation_types,id',
            'value' => 'required|string',
            'color_hex' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        VariationValue::create($data);

        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison créée'));
    }

    public function edit(VariationValue $variationValue)
    {
        $types = VariationType::all();
        return view('variation_values.edit', compact('variationValue', 'types'));
    }

    public function update(Request $request, VariationValue $variationValue)
    {
        $data = $request->validate([
            'variation_type_id' => 'required|exists:variation_types,id',
            'value' => 'required|string',
            'color_hex' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $data['color_hex'] = $data['color_hex'] ?? null;
        $variationValue->update($data);

        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison mise à jour'));
    }

    public function destroy(VariationValue $variationValue)
    {
        $variationValue->delete();
        return redirect()->route('variation-values.index')
                         ->with('success', __('Valeur de déclinaison supprimée'));
    }

    /**
     * Audit dashboard: lists all "suspect" values per type with pre-detected issues
     * and lets the user record a decision per row (action + target + notes).
     * Decisions are persisted in variation_values.audit_decision (JSON) so they
     * can be replayed later by cleanup commands.
     */
    public function audit(Request $request)
    {
        $types = VariationType::orderBy('name')->get()->keyBy('id');

        // Usage counts per value (number of product attribute rows)
        $usage = DB::table('product_variation_attributes')
            ->select('variation_value_id', DB::raw('count(*) as n'))
            ->groupBy('variation_value_id')
            ->pluck('n', 'variation_value_id');

        // Sample products per value (max 3)
        $samples = $this->buildSampleProductsMap();

        $values = VariationValue::with('type')->orderBy('variation_type_id')->orderBy('value')->get();

        $rows = [];
        foreach ($values as $v) {
            $reasons = $this->detectIssues($v);
            $count = (int) ($usage[$v->id] ?? 0);

            // A value is shown if: has any issue, OR is orphan (count=0), OR has a saved decision
            if (empty($reasons) && $count > 0 && empty($v->audit_decision)) {
                continue;
            }

            $rows[] = [
                'value' => $v,
                'reasons' => $reasons,
                'count' => $count,
                'samples' => $samples[$v->id] ?? [],
                'suggestion' => $this->suggestAction($v, $reasons, $count),
            ];
        }

        // Group by type for display
        $byType = collect($rows)->groupBy(fn($r) => $r['value']->variation_type_id);

        // Stats
        $stats = [
            'total' => count($rows),
            'orphans' => collect($rows)->where('count', 0)->count(),
            'with_issues' => collect($rows)->filter(fn($r) => !empty($r['reasons']))->count(),
            'decided' => collect($rows)->filter(fn($r) => !empty($r['value']->audit_decision))->count(),
        ];

        return view('variation_values.audit', [
            'byType' => $byType,
            'types' => $types,
            'allTypes' => VariationType::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    /**
     * Persist the staff decision for a single variation_value (POST AJAX).
     * Body: { action, target_type_id?, new_value?, merge_into_id?, split_into?, notes? }
     */
    public function saveAuditDecision(Request $request, VariationValue $variationValue)
    {
        // Drop empty split rows (browsers may submit them when action != 'split')
        if ($request->has('split_into') && is_array($request->input('split_into'))) {
            $clean = array_values(array_filter(
                $request->input('split_into'),
                fn($r) => is_array($r) && !empty($r['type_id']) && !empty($r['value'])
            ));
            $request->merge(['split_into' => $clean ?: null]);
        }

        $action = $request->input('action');

        $data = $request->validate([
            'action' => 'required|in:keep,rename,move,split,merge,delete,clear',
            'target_type_id' => $action === 'move' ? 'required|exists:variation_types,id' : 'nullable|exists:variation_types,id',
            'new_value' => $action === 'rename' ? 'required|string|max:191' : 'nullable|string|max:191',
            'merge_into_id' => $action === 'merge' ? 'required|exists:variation_values,id' : 'nullable|exists:variation_values,id',
            'split_into' => $action === 'split' ? 'required|array|min:2' : 'nullable|array',
            'split_into.*.type_id' => 'required_with:split_into|exists:variation_types,id',
            'split_into.*.value' => 'required_with:split_into|string|max:191',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($data['action'] === 'clear') {
            $variationValue->update([
                'audit_decision' => null,
                'audit_decided_at' => null,
            ]);
            return response()->json(['ok' => true, 'cleared' => true]);
        }

        $decision = array_filter([
            'action' => $data['action'],
            'target_type_id' => $data['target_type_id'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'merge_into_id' => $data['merge_into_id'] ?? null,
            'split_into' => $data['split_into'] ?? null,
            'notes' => $data['notes'] ?? null,
            'decided_by' => $request->user()?->id,
        ], fn($v) => $v !== null && $v !== '');

        $variationValue->update([
            'audit_decision' => $decision,
            'audit_decided_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'decision' => $decision,
            'decided_at' => $variationValue->audit_decided_at?->toIso8601String(),
        ]);
    }

    /** Detect issues for a single value. Returns array of reason codes. */
    private function detectIssues(VariationValue $v): array
    {
        $reasons = [];
        $type = $v->type?->name ?? '';
        $val = trim((string) $v->value);

        if ($val === '') {
            $reasons[] = 'empty';
            return $reasons;
        }

        // Pattern check per type
        $key = strtolower($type);
        if (isset(self::TYPE_PATTERNS[$key]) && !preg_match(self::TYPE_PATTERNS[$key], $val)) {
            $reasons[] = 'pattern_mismatch';
        }

        // Color-specific forbidden tokens
        if ($key === 'color' && preg_match(self::COLOR_FORBIDDEN, $val)) {
            $reasons[] = 'color_contains_unit_or_size';
        }

        // Color must have hex (new policy 2026-04-30)
        if ($key === 'color' && empty($v->color_hex)) {
            $reasons[] = 'color_missing_hex';
        }

        // Compound size like "GREEN 45", "BLACK XL"
        if ($key === 'size' && preg_match('/^(BLACK|WHITE|RED|GREEN|BLUE|ORANGE|YELLOW|PINK|GREY|GRAY|BEIGE|NAVY|GOLD|SILVER|BROWN|PURPLE|CREAM|IVORY|MAROON|TEAL|OLIVE|LIME|MINT|TURQUOISE|FUCHSIA|SAFFRON|LAVENDER|BLUEBIC|SHIRT|SKIRT)[\s\-]+/i', $val)) {
            $reasons[] = 'compound_color_size';
        }

        // EAN-like (long digit sequence) in non-numeric type
        if (preg_match('/^\d{8,}$/', $val)) {
            $reasons[] = 'looks_like_ean';
        }

        // Suspiciously long (likely full product name)
        if (strlen($val) > 25 && str_word_count($val) >= 3) {
            $reasons[] = 'too_long';
        }

        // Case duplicate (another value with same uppercased trimmed form, same type)
        $norm = strtoupper(preg_replace('/\s+/', ' ', $val));
        $dup = VariationValue::where('variation_type_id', $v->variation_type_id)
            ->where('id', '!=', $v->id)
            ->whereRaw('UPPER(TRIM(value)) = ?', [$norm])
            ->exists();
        if ($dup) {
            $reasons[] = 'case_duplicate';
        }

        return $reasons;
    }

    /** Build a sample products map: [value_id => [['id'=>.., 'name'=>..], ...]] */
    private function buildSampleProductsMap(): array
    {
        $rows = DB::table('product_variation_attributes as pva')
            ->join('products as p', 'p.id', '=', 'pva.product_id')
            ->select('pva.variation_value_id', 'p.id', 'p.name')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            if (!isset($map[$r->variation_value_id])) {
                $map[$r->variation_value_id] = [];
            }
            if (count($map[$r->variation_value_id]) >= 3) continue;

            $name = $r->name;
            if (is_string($name) && str_starts_with($name, '{')) {
                $arr = json_decode($name, true);
                $name = $arr['en'] ?? $arr['fr'] ?? reset($arr) ?: $name;
            }
            $map[$r->variation_value_id][] = ['id' => $r->id, 'name' => $name];
        }
        return $map;
    }

    /** Suggest a default action based on detected issues. */
    private function suggestAction(VariationValue $v, array $reasons, int $count): ?array
    {
        if ($count === 0) {
            return ['action' => 'delete', 'reason' => 'orphan'];
        }
        if (in_array('looks_like_ean', $reasons, true)) {
            return ['action' => 'delete', 'reason' => 'ean_in_variation'];
        }
        if (in_array('case_duplicate', $reasons, true)) {
            return ['action' => 'merge', 'reason' => 'case_dup'];
        }
        if (in_array('compound_color_size', $reasons, true)) {
            return ['action' => 'split', 'reason' => 'compound'];
        }
        if (in_array('color_contains_unit_or_size', $reasons, true)) {
            $val = strtoupper($v->value);
            if (preg_match('/\d+\s?(G|KG|MG)\b/i', $val)) {
                return ['action' => 'move', 'target' => 'weight', 'reason' => 'unit_g'];
            }
            if (preg_match('/\d+\s?(ML|CL|L)\b/i', $val)) {
                return ['action' => 'move', 'target' => 'capacity', 'reason' => 'unit_ml'];
            }
        }
        return null;
    }
}
