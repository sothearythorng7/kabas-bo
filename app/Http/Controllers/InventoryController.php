<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\Brand;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display inventory management page.
     */
    public function index()
    {
        $stores = Store::orderBy('name')->get();
        return view('inventory.index', compact('stores'));
    }

    /**
     * Export inventory template Excel file.
     */
    public function export(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $store = Store::findOrFail($request->store_id);

        // Get all products with their current stock for this store
        $products = Product::with(['brand'])
            ->orderBy('name->fr')
            ->get();

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaire');

        // Set headers
        $headers = [
            'A1' => 'ID Produit',
            'B1' => 'Nom',
            'C1' => 'Marque',
            'D1' => 'EAN',
            'E1' => 'Stock Théorique',
            'F1' => 'Stock Réel (à compléter)',
            'G1' => 'Différence',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9E1F2');
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Fill data
        $row = 2;
        foreach ($products as $product) {
            // Calculate theoretical stock from batches
            $theoreticalStock = StockBatch::where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->whereNull('reseller_id')
                ->sum('quantity');

            $productName = $product->name['fr'] ?? $product->name['en'] ?? 'N/A';

            $sheet->setCellValue("A{$row}", $product->id);
            $sheet->setCellValue("B{$row}", $productName);
            $sheet->setCellValue("C{$row}", $product->brand?->name ?? '-');
            $sheet->setCellValue("D{$row}", $product->ean ?? '-');
            $sheet->setCellValue("E{$row}", $theoreticalStock);
            $sheet->setCellValue("F{$row}", ''); // To be filled manually
            $sheet->setCellValue("G{$row}", "=IF(F{$row}<>\"\",F{$row}-E{$row},\"\")"); // Formula for difference

            // Lock cells except F column
            $sheet->getStyle("A{$row}:E{$row}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);
            $sheet->getStyle("G{$row}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);
            $sheet->getStyle("F{$row}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

            $row++;
        }

        // Enable sheet protection (users can only edit column F)
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('inventory2024');

        // Generate filename
        $filename = 'inventaire_' . str_replace(' ', '_', $store->name) . '_' . date('Y-m-d_His') . '.xlsx';

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import inventory data from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'inventory_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $store = Store::findOrFail($request->store_id);
        $file = $request->file('inventory_file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $updates = [];
            $errors = [];

            // Start from row 2 (skip header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $productId = $sheet->getCell("A{$row}")->getValue();
                $theoreticalStock = $sheet->getCell("E{$row}")->getValue();
                $realStock = $sheet->getCell("F{$row}")->getValue();

                // Skip if real stock is empty
                if ($realStock === null || $realStock === '') {
                    continue;
                }

                $realStock = (int)$realStock;
                $theoreticalStock = (int)$theoreticalStock;
                $difference = $realStock - $theoreticalStock;

                if ($difference != 0) {
                    $product = Product::find($productId);
                    if ($product) {
                        $updates[] = [
                            'product_id' => $productId,
                            'product_name' => $product->name['fr'] ?? $product->name['en'] ?? 'N/A',
                            'theoretical' => $theoreticalStock,
                            'real' => $realStock,
                            'difference' => $difference,
                        ];
                    }
                }
            }

            if (empty($updates)) {
                return redirect()->route('inventory.index')
                    ->with('info', 'Aucune différence détectée dans l\'inventaire.');
            }

            // Store data in session for confirmation
            session(['inventory_updates' => $updates, 'inventory_store_id' => $store->id]);

            return redirect()->route('inventory.confirm');

        } catch (\Exception $e) {
            return redirect()->route('inventory.index')
                ->with('error', 'Erreur lors de la lecture du fichier: ' . $e->getMessage());
        }
    }

    /**
     * Show confirmation page for inventory adjustments.
     */
    public function confirm()
    {
        $updates = session('inventory_updates');
        $storeId = session('inventory_store_id');

        if (!$updates || !$storeId) {
            return redirect()->route('inventory.index')
                ->with('error', 'Aucune donnée d\'inventaire à confirmer.');
        }

        $store = Store::findOrFail($storeId);

        return view('inventory.confirm', compact('updates', 'store'));
    }

    /**
     * Apply inventory adjustments.
     */
    public function apply(Request $request)
    {
        $updates = session('inventory_updates');
        $storeId = session('inventory_store_id');

        if (!$updates || !$storeId) {
            return redirect()->route('inventory.index')
                ->with('error', 'Aucune donnée d\'inventaire à appliquer.');
        }

        $store = Store::findOrFail($storeId);

        DB::beginTransaction();

        try {
            foreach ($updates as $update) {
                $productId = $update['product_id'];
                $difference = $update['difference'];

                if ($difference > 0) {
                    // Add stock - create a new batch with zero cost (inventory adjustment)
                    StockBatch::create([
                        'product_id' => $productId,
                        'store_id' => $storeId,
                        'reseller_id' => null,
                        'quantity' => $difference,
                        'unit_price' => 0,
                        'source_delivery_id' => null,
                        'source_supplier_order_id' => null,
                        'source_refill_id' => null,
                    ]);
                } else {
                    // Remove stock - deduct from existing batches (FIFO)
                    $quantityToRemove = abs($difference);

                    $batches = StockBatch::where('product_id', $productId)
                        ->where('store_id', $storeId)
                        ->whereNull('reseller_id')
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($batches as $batch) {
                        if ($quantityToRemove <= 0) break;

                        if ($batch->quantity >= $quantityToRemove) {
                            $batch->quantity -= $quantityToRemove;
                            $batch->save();
                            $quantityToRemove = 0;
                        } else {
                            $quantityToRemove -= $batch->quantity;
                            $batch->quantity = 0;
                            $batch->save();
                        }
                    }
                }
            }

            DB::commit();

            // Clear session data
            session()->forget(['inventory_updates', 'inventory_store_id']);

            return redirect()->route('inventory.index')
                ->with('success', 'Inventaire mis à jour avec succès. ' . count($updates) . ' produit(s) ajusté(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('inventory.index')
                ->with('error', 'Erreur lors de la mise à jour de l\'inventaire: ' . $e->getMessage());
        }
    }

    /**
     * Cancel inventory import.
     */
    public function cancel()
    {
        session()->forget(['inventory_updates', 'inventory_store_id']);
        return redirect()->route('inventory.index')
            ->with('info', 'Import d\'inventaire annulé.');
    }
}
