<?php

namespace App\Http\Controllers;

use App\Models\PopupEvent;
use App\Models\PopupEventItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PopupEventController extends Controller
{
    public function index(Request $request)
    {
        $query = PopupEvent::with(['store', 'createdBy'])->latest();

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->paginate(20)->withQueryString();
        $stores = Store::orderBy('name')->get();

        return view('popup-events.index', compact('events', 'stores'));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get();

        return view('popup-events.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'store_id' => 'required|exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity_allocated' => 'required|integer|min:1',
        ]);

        $event = DB::transaction(function () use ($request) {
            $event = PopupEvent::create([
                'reference' => PopupEvent::generateReference(),
                'name' => $request->name,
                'location' => $request->location,
                'store_id' => $request->store_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'notes' => $request->notes,
                'created_by_user_id' => auth()->id(),
                'status' => 'planned',
            ]);

            foreach ($request->products as $item) {
                if ((int) $item['quantity_allocated'] <= 0) continue;

                PopupEventItem::create([
                    'popup_event_id' => $event->id,
                    'product_id' => $item['product_id'],
                    'quantity_allocated' => (int) $item['quantity_allocated'],
                ]);
            }

            return $event;
        });

        return redirect()->route('popup-events.show', $event)
            ->with('success', __('messages.popup_event.created'));
    }

    public function show(PopupEvent $popupEvent)
    {
        $popupEvent->load(['store', 'createdBy', 'items.product.brand', 'shifts.sales']);

        $stats = null;
        if ($popupEvent->isActive() || $popupEvent->isCompleted()) {
            $sales = Sale::whereIn('shift_id', $popupEvent->shifts->pluck('id'))->get();
            $stats = [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('total'),
                'avg_basket' => $sales->count() > 0 ? round($sales->sum('total') / $sales->count(), 5) : 0,
            ];
        }

        return view('popup-events.show', compact('popupEvent', 'stats'));
    }

    public function edit(PopupEvent $popupEvent)
    {
        if (!$popupEvent->isEditable()) {
            return redirect()->route('popup-events.show', $popupEvent)
                ->with('error', __('messages.popup_event.cannot_edit'));
        }

        $popupEvent->load(['items.product.brand']);
        $stores = Store::orderBy('name')->get();

        return view('popup-events.edit', compact('popupEvent', 'stores'));
    }

    public function update(Request $request, PopupEvent $popupEvent)
    {
        if (!$popupEvent->isEditable()) {
            return redirect()->route('popup-events.show', $popupEvent)
                ->with('error', __('messages.popup_event.cannot_edit'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'store_id' => 'required|exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity_allocated' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $popupEvent) {
            $popupEvent->update([
                'name' => $request->name,
                'location' => $request->location,
                'store_id' => $request->store_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'notes' => $request->notes,
            ]);

            $popupEvent->items()->delete();

            foreach ($request->products as $item) {
                if ((int) $item['quantity_allocated'] <= 0) continue;

                PopupEventItem::create([
                    'popup_event_id' => $popupEvent->id,
                    'product_id' => $item['product_id'],
                    'quantity_allocated' => (int) $item['quantity_allocated'],
                ]);
            }
        });

        return redirect()->route('popup-events.show', $popupEvent)
            ->with('success', __('messages.popup_event.updated'));
    }

    public function activate(PopupEvent $popupEvent)
    {
        if (!$popupEvent->isPlanned()) {
            return redirect()->back()->with('error', __('messages.popup_event.cannot_activate'));
        }

        $popupEvent->load('items');
        $store = Store::findOrFail($popupEvent->store_id);

        // Verify stock availability
        foreach ($popupEvent->items as $item) {
            $product = Product::findOrFail($item->product_id);
            $available = $product->getAvailableStock($store);
            if ($available < $item->quantity_allocated) {
                $name = is_array($product->name) ? ($product->name[app()->getLocale()] ?? reset($product->name)) : $product->name;
                return redirect()->back()->with('error', __('messages.popup_event.insufficient_stock', [
                    'product' => $name,
                    'available' => $available,
                    'requested' => $item->quantity_allocated,
                ]));
            }
        }

        $popupEvent->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return redirect()->route('popup-events.show', $popupEvent)
            ->with('success', __('messages.popup_event.activated'));
    }

    public function complete(PopupEvent $popupEvent)
    {
        if (!$popupEvent->isActive()) {
            return redirect()->back()->with('error', __('messages.popup_event.cannot_complete'));
        }

        DB::transaction(function () use ($popupEvent) {
            $popupEvent->load('items', 'shifts.sales.items');

            // Calculate quantity sold per product from event shifts
            $soldByProduct = [];
            foreach ($popupEvent->shifts as $shift) {
                foreach ($shift->sales as $sale) {
                    foreach ($sale->items as $saleItem) {
                        $pid = $saleItem->product_id;
                        if ($pid) {
                            $soldByProduct[$pid] = ($soldByProduct[$pid] ?? 0) + $saleItem->quantity;
                        }
                    }
                }
            }

            foreach ($popupEvent->items as $item) {
                $item->update([
                    'quantity_sold' => $soldByProduct[$item->product_id] ?? 0,
                ]);
            }

            $popupEvent->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('popup-events.show', $popupEvent)
            ->with('success', __('messages.popup_event.completed'));
    }

    public function cancel(PopupEvent $popupEvent)
    {
        if (!in_array($popupEvent->status, ['planned', 'active'])) {
            return redirect()->back()->with('error', __('messages.popup_event.cannot_cancel'));
        }

        $popupEvent->update(['status' => 'cancelled']);

        return redirect()->route('popup-events.show', $popupEvent)
            ->with('success', __('messages.popup_event.cancelled'));
    }

    public function destroy(PopupEvent $popupEvent)
    {
        if (!$popupEvent->isPlanned()) {
            return redirect()->back()->with('error', __('messages.popup_event.cannot_delete'));
        }

        $popupEvent->delete();

        return redirect()->route('popup-events.index')
            ->with('success', __('messages.popup_event.deleted'));
    }

    public function searchProducts(Request $request, Store $store)
    {
        $request->validate(['q' => 'required|string|min:1']);

        $locale = app()->getLocale();

        $products = Product::search($request->q)
            ->take(20)
            ->get()
            ->map(function ($product) use ($store, $locale) {
                $stock = $product->getAvailableStock($store);
                if ($stock <= 0) return null;

                $name = is_array($product->name)
                    ? ($product->name[$locale] ?? reset($product->name))
                    : $product->name;

                return [
                    'id' => $product->id,
                    'name' => $name,
                    'ean' => $product->ean,
                    'brand' => $product->brand?->name,
                    'stock' => $stock,
                    'price' => $product->price,
                ];
            })
            ->filter()
            ->values();

        return response()->json($products);
    }

    public function activeEventsApi($storeId)
    {
        $events = PopupEvent::where('store_id', $storeId)
            ->where('status', 'active')
            ->select('id', 'name', 'store_id', 'location', 'start_date', 'end_date')
            ->get();

        return response()->json($events);
    }
}
