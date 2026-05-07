<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromotionAction;
use App\Models\PromotionCode;
use App\Models\PromotionCondition;
use App\Models\PromotionRule;
use App\Services\PromotionTypeCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $query = PromotionRule::query()->withCount(['conditions', 'actions', 'codes']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('mode')) {
            $query->where('activation_mode', $request->input('mode'));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $rules = $query->orderByDesc('priority')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('promotions.index', compact('rules'));
    }

    public function create()
    {
        $rule = new PromotionRule([
            'status' => PromotionRule::STATUS_DRAFT,
            'activation_mode' => PromotionRule::MODE_AUTOMATIC,
            'conditions_logic' => PromotionRule::LOGIC_ALL,
            'channel' => PromotionRule::CHANNEL_WEBSITE,
            'priority' => 0,
        ]);
        $rule->setRelation('conditions', collect());
        $rule->setRelation('actions', collect());
        $rule->setRelation('codes', collect());

        return view('promotions.form', $this->formData($rule));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $rule = DB::transaction(fn () => $this->persist(null, $data));

        return redirect()->route('promotions.edit', $rule)->with('success', __('messages.promotion.saved'));
    }

    public function edit(PromotionRule $promotion)
    {
        $promotion->load(['conditions', 'actions', 'codes']);

        return view('promotions.form', $this->formData($promotion));
    }

    public function update(Request $request, PromotionRule $promotion)
    {
        $data = $this->validated($request);
        DB::transaction(fn () => $this->persist($promotion, $data));

        return redirect()->route('promotions.edit', $promotion)->with('success', __('messages.promotion.saved'));
    }

    public function destroy(PromotionRule $promotion)
    {
        $promotion->delete();

        return redirect()->route('promotions.index')->with('success', __('messages.promotion.deleted'));
    }

    public function storeCode(Request $request, PromotionRule $promotion)
    {
        $data = $request->validate([
            'code' => 'required|string|max:64|unique:promotion_codes,code',
            'max_uses' => 'nullable|integer|min:0',
            'per_customer_limit' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'sometimes|boolean',
        ]);

        $promotion->codes()->create(array_merge($data, [
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => auth()->id(),
        ]));

        return back()->with('success', __('messages.promotion.code_added'));
    }

    public function destroyCode(PromotionRule $promotion, PromotionCode $code)
    {
        abort_unless($code->promotion_rule_id === $promotion->id, 404);
        $code->delete();

        return back()->with('success', __('messages.promotion.code_deleted'));
    }

    protected function formData(PromotionRule $rule): array
    {
        $locale = app()->getLocale();
        $fallback = 'en';

        $categories = Category::with(['translations' => function ($q) use ($locale, $fallback) {
                $q->whereIn('locale', array_unique([$locale, $fallback]));
            }])
            ->orderBy('id')
            ->get(['id', 'parent_id'])
            ->map(function ($c) use ($locale, $fallback) {
                $trans = $c->translations->keyBy('locale');
                $name = $trans[$locale]->name ?? $trans[$fallback]->name ?? ('Cat #'.$c->id);
                return (object) ['id' => $c->id, 'name' => $name, 'parent_id' => $c->parent_id];
            });

        return [
            'rule' => $rule,
            'conditionCatalog' => PromotionTypeCatalog::conditions(),
            'actionCatalog' => PromotionTypeCatalog::actions(),
            'categories' => $categories,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'topProducts' => Product::where('is_active', true)->orderBy('id', 'desc')->limit(100)->get(['id', 'name', 'ean']),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,active,paused,expired,archived',
            'activation_mode' => 'required|in:automatic,code_required',
            'conditions_logic' => 'required|in:all,any',
            'channel' => 'required|in:website,pos,both',
            'priority' => 'nullable|integer',
            'is_exclusive' => 'sometimes|boolean',
            'stackable_group' => 'nullable|string|max:64',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses_total' => 'nullable|integer|min:0',
            'max_uses_per_customer' => 'nullable|integer|min:0',
            'max_budget' => 'nullable|numeric|min:0',
            'conditions' => 'nullable|array',
            'conditions.*.type' => 'required_with:conditions|string|max:64',
            'conditions.*.operator' => 'nullable|string|in:eq,neq,gte,lte,in,not_in',
            'conditions.*.params' => 'nullable|array',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required_with:actions|string|max:64',
            'actions.*.params' => 'nullable|array',
        ]);
    }

    protected function persist(?PromotionRule $rule, array $data): PromotionRule
    {
        $payload = [
            'name' => $data['name'],
            'status' => $data['status'],
            'activation_mode' => $data['activation_mode'],
            'conditions_logic' => $data['conditions_logic'],
            'channel' => $data['channel'],
            'priority' => (int) ($data['priority'] ?? 0),
            'is_exclusive' => (bool) ($data['is_exclusive'] ?? false),
            'stackable_group' => $data['stackable_group'] ?: null,
            'starts_at' => $data['starts_at'] ?: null,
            'ends_at' => $data['ends_at'] ?: null,
            'max_uses_total' => ($data['max_uses_total'] ?? null) ?: null,
            'max_uses_per_customer' => ($data['max_uses_per_customer'] ?? null) ?: null,
            'max_budget' => ($data['max_budget'] ?? null) ?: null,
        ];

        if ($rule) {
            $rule->update($payload);
        } else {
            $payload['created_by_user_id'] = auth()->id();
            $rule = PromotionRule::create($payload);
        }

        $this->syncConditions($rule, $data['conditions'] ?? []);
        $this->syncActions($rule, $data['actions'] ?? []);

        return $rule;
    }

    protected function syncConditions(PromotionRule $rule, array $conditions): void
    {
        $rule->conditions()->delete();
        foreach (array_values($conditions) as $position => $c) {
            $catalog = PromotionTypeCatalog::conditionByKey($c['type']);
            if (! $catalog) continue;

            PromotionCondition::create([
                'promotion_rule_id' => $rule->id,
                'type' => $c['type'],
                'operator' => $catalog['supports_operator'] ? ($c['operator'] ?? null) : null,
                'params' => $this->normalizeParams($c['params'] ?? [], $catalog),
                'position' => $position,
            ]);
        }
    }

    protected function syncActions(PromotionRule $rule, array $actions): void
    {
        $rule->actions()->delete();
        foreach (array_values($actions) as $position => $a) {
            $catalog = PromotionTypeCatalog::actionByKey($a['type']);
            if (! $catalog) continue;

            PromotionAction::create([
                'promotion_rule_id' => $rule->id,
                'type' => $a['type'],
                'params' => $this->normalizeParams($a['params'] ?? [], $catalog),
                'position' => $position,
            ]);
        }
    }

    protected function normalizeParams(array $raw, array $catalog): array
    {
        $out = [];
        foreach ($catalog['fields'] as $field) {
            $val = $raw[$field['key']] ?? null;
            if ($val === null || $val === '') {
                if (isset($field['default'])) {
                    $out[$field['key']] = $field['default'];
                }
                continue;
            }

            switch ($field['type']) {
                case 'decimal':
                    $out[$field['key']] = (float) $val;
                    break;
                case 'integer':
                    $out[$field['key']] = (int) $val;
                    break;
                case 'category_multi':
                case 'brand_multi':
                case 'country_multi':
                    $out[$field['key']] = array_values(array_filter(is_array($val) ? $val : explode(',', $val)));
                    if ($field['type'] === 'country_multi') {
                        $out[$field['key']] = array_map(fn ($v) => strtoupper(trim($v)), $out[$field['key']]);
                    } else {
                        $out[$field['key']] = array_map('intval', $out[$field['key']]);
                    }
                    break;
                case 'product':
                    $out[$field['key']] = (int) $val;
                    break;
                default:
                    $out[$field['key']] = $val;
            }
        }

        return $out;
    }
}
