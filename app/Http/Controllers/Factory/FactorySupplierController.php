<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class FactorySupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::rawMaterialSuppliers()
            ->withCount('rawMaterials')
            ->orderBy('name')
            ->paginate(20);

        return view('factory.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('factory.suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['is_raw_material_supplier'] = true;
        $data['type'] = 'buyer'; // Les fournisseurs de matières premières sont en achat direct

        Supplier::create($data);

        return redirect()->route('factory.suppliers.index')
            ->with('success', __('messages.common.created'));
    }

    public function edit(Request $request, Supplier $supplier)
    {
        // Vérifier que c'est bien un fournisseur de matières premières
        if (!$supplier->is_raw_material_supplier) {
            abort(404);
        }

        $supplier->load(['rawMaterials', 'contacts']);

        // Paginer les commandes avec filtre par statut
        $ordersQuery = $supplier->orders()
            ->where('order_type', 'raw_material')
            ->with('rawMaterials')
            ->latest();

        if ($request->filled('status')) {
            if ($request->status === 'received_unpaid') {
                $ordersQuery->where('status', 'received')->where('is_paid', false);
            } elseif ($request->status === 'received_paid') {
                $ordersQuery->where('status', 'received')->where('is_paid', true);
            } else {
                $ordersQuery->where('status', $request->status);
            }
        }

        $orders = $ordersQuery->paginate(15);

        // Compter les matières premières
        $rawMaterialsCount = $supplier->rawMaterials->count();
        $contactsCount = $supplier->contacts->count();
        $ordersCount = $orders->total();

        // Calculer les commandes impayées
        $unpaidOrdersCount = $supplier->orders()
            ->where('order_type', 'raw_material')
            ->where('status', 'received')
            ->where('is_paid', false)
            ->count();

        $totalUnpaidAmount = $supplier->orders()
            ->where('order_type', 'raw_material')
            ->where('status', 'received')
            ->where('is_paid', false)
            ->get()
            ->sum(fn($order) => $order->invoicedAmount() ?: $order->expectedAmount());

        return view('factory.suppliers.edit', compact(
            'supplier',
            'orders',
            'rawMaterialsCount',
            'contactsCount',
            'ordersCount',
            'unpaidOrdersCount',
            'totalUnpaidAmount'
        ));
    }

    public function update(Request $request, Supplier $supplier)
    {
        // Vérifier que c'est bien un fournisseur de matières premières
        if (!$supplier->is_raw_material_supplier) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $supplier->update($data);

        return redirect()->route('factory.suppliers.index')
            ->with('success', __('messages.common.updated'));
    }

    public function destroy(Supplier $supplier)
    {
        // Vérifier que c'est bien un fournisseur de matières premières
        if (!$supplier->is_raw_material_supplier) {
            abort(404);
        }

        if ($supplier->rawMaterials()->exists()) {
            return back()->with('error', __('messages.factory.supplier_has_materials'));
        }

        $supplier->delete();

        return redirect()->route('factory.suppliers.index')
            ->with('success', __('messages.common.deleted'));
    }
}
