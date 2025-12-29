<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RawMaterialInventoryController extends Controller
{
    /**
     * Affiche la page d'inventaire
     */
    public function index()
    {
        return view('factory.inventory.index');
    }

    /**
     * Télécharger le fichier Excel pour l'inventaire
     */
    public function export(): StreamedResponse
    {
        $materials = RawMaterial::where('track_stock', true)
            ->orderBy('name')
            ->get();

        $filename = 'inventory_raw_materials_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($materials) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($handle, [
                'id',
                'name',
                'unit',
                'theoretical_stock',
                'counted_stock'
            ], ';');

            // Data
            foreach ($materials as $material) {
                fputcsv($handle, [
                    $material->id,
                    $material->name,
                    $material->unit,
                    number_format($material->total_stock, 2, '.', ''),
                    '' // Colonne vide pour la saisie
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Détecter le séparateur CSV utilisé dans le fichier
     */
    private function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        $firstLine = fgets($handle);
        fclose($handle);

        // Count occurrences of common delimiters
        $delimiters = [';' => 0, ',' => 0, "\t" => 0];
        foreach ($delimiters as $delimiter => &$count) {
            $count = substr_count($firstLine, $delimiter);
        }

        // Return the delimiter with most occurrences (prefer ; in case of tie)
        arsort($delimiters);
        $detected = array_key_first($delimiters);

        return $delimiters[$detected] > 0 ? $detected : ';';
    }

    /**
     * Trouver l'index de la colonne counted_stock dans le header
     */
    private function findCountedStockColumn(array $header): int
    {
        $possibleNames = ['counted_stock', 'quantite', 'quantité', 'quantity', 'counted', 'stock_compte', 'stock_compté'];

        foreach ($header as $index => $columnName) {
            $normalized = strtolower(trim($columnName));
            if (in_array($normalized, $possibleNames)) {
                return $index;
            }
        }

        // Default to column 4 (5th column) as per original export format
        return min(4, count($header) - 1);
    }

    /**
     * Importer le fichier d'inventaire et mettre à jour les stocks
     */
    public function import(Request $request)
    {
        $request->validate([
            'inventory_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('inventory_file');
        $path = $file->getRealPath();

        $updatedCount = 0;
        $errors = [];
        $adjustments = [];

        // Detect the CSV delimiter
        $delimiter = $this->detectCsvDelimiter($path);
        $debugInfo = ['delimiter' => $delimiter === "\t" ? 'TAB' : $delimiter];

        DB::transaction(function () use ($path, $delimiter, &$updatedCount, &$errors, &$adjustments, &$debugInfo) {
            $handle = fopen($path, 'r');

            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }

            // Read and parse header to find counted_stock column
            $header = fgetcsv($handle, 0, $delimiter);
            $debugInfo['header'] = $header;
            $debugInfo['header_count'] = count($header);

            $countedStockIndex = $this->findCountedStockColumn($header);
            $debugInfo['counted_stock_index'] = $countedStockIndex;

            // Find ID column (should be first, but let's be safe)
            $idIndex = 0;
            foreach ($header as $index => $columnName) {
                if (strtolower(trim($columnName)) === 'id') {
                    $idIndex = $index;
                    break;
                }
            }
            $debugInfo['id_index'] = $idIndex;

            $lineNumber = 1;
            $linesProcessed = 0;
            $linesSkipped = [];

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;
                $linesProcessed++;

                if (count($data) <= max($idIndex, $countedStockIndex)) {
                    $linesSkipped[] = "Line $lineNumber: not enough columns (" . count($data) . " cols)";
                    continue;
                }

                $id = trim($data[$idIndex]);
                $countedStock = isset($data[$countedStockIndex]) ? trim($data[$countedStockIndex]) : '';

                // Log first few rows for debugging
                if ($linesProcessed <= 3) {
                    $debugInfo['sample_rows'][] = [
                        'line' => $lineNumber,
                        'raw_data' => $data,
                        'id' => $id,
                        'counted_stock_raw' => $countedStock,
                    ];
                }

                // Skip if no counted stock entered
                if ($countedStock === '' || $countedStock === null) {
                    continue;
                }

                // Validate counted stock is numeric
                $countedStock = str_replace(',', '.', $countedStock);
                if (!is_numeric($countedStock)) {
                    $errors[] = __('messages.factory.inventory.invalid_value_line', ['line' => $lineNumber]);
                    continue;
                }

                $countedStock = (float) $countedStock;

                // Find the raw material
                $material = RawMaterial::find($id);
                if (!$material) {
                    $errors[] = __('messages.factory.inventory.material_not_found_line', ['line' => $lineNumber, 'id' => $id]);
                    continue;
                }

                if (!$material->track_stock) {
                    continue;
                }

                $currentStock = $material->total_stock;
                $difference = $countedStock - $currentStock;

                // Skip if no difference
                if (abs($difference) < 0.001) {
                    continue;
                }

                // Create adjustment batch
                $batch = RawMaterialStockBatch::create([
                    'raw_material_id' => $material->id,
                    'quantity' => $difference,
                    'unit_price' => 0,
                    'received_at' => now(),
                    'batch_number' => 'INV-' . now()->format('Ymd-His'),
                    'notes' => __('messages.factory.inventory.adjustment_note', [
                        'from' => number_format($currentStock, 2),
                        'to' => number_format($countedStock, 2)
                    ]),
                ]);

                // Record stock movement
                $material->stockMovements()->create([
                    'quantity' => $difference,
                    'type' => 'adjustment',
                    'source_type' => RawMaterialStockBatch::class,
                    'source_id' => $batch->id,
                    'notes' => __('messages.factory.inventory.inventory_adjustment'),
                    'user_id' => auth()->id(),
                ]);

                $adjustments[] = [
                    'name' => $material->name,
                    'from' => $currentStock,
                    'to' => $countedStock,
                    'difference' => $difference,
                ];

                $updatedCount++;
            }

            $debugInfo['lines_processed'] = $linesProcessed;
            $debugInfo['lines_skipped'] = $linesSkipped;

            fclose($handle);
        });

        // Log debug info
        Log::info('Raw Material Inventory Import Debug', $debugInfo);

        return redirect()->route('factory.inventory.index')
            ->with('success', __('messages.factory.inventory.import_success', ['count' => $updatedCount]))
            ->with('adjustments', $adjustments)
            ->with('import_errors', $errors)
            ->with('debug_info', $debugInfo);
    }
}
