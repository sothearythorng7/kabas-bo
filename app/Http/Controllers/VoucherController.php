<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Store;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function index(Request $request)
    {
        $query = Voucher::with(['createdAtStore', 'createdByUser', 'usedAtStore', 'sourceExchange']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('store_id')) {
            $query->where('created_at_store_id', $request->store_id);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $vouchers = $query->orderBy('created_at', 'desc')->paginate(50);
        $stores = Store::all();

        // Statistics
        $stats = $this->voucherService->getStatistics(
            $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from) : null,
            $request->filled('date_to') ? \Carbon\Carbon::parse($request->date_to) : null,
            $request->store_id
        );

        return view('vouchers.index', compact('vouchers', 'stores', 'stats'));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load([
            'createdAtStore',
            'createdByUser',
            'usedAtStore',
            'usedInSale',
            'sourceExchange.originalSale',
            'cancelledByUser'
        ]);

        return view('vouchers.show', compact('voucher'));
    }

    public function create()
    {
        $stores = Store::all();
        return view('vouchers.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000',
            'store_id' => 'nullable|exists:stores,id',
            'validity' => 'required|in:1_month,3_months,6_months,1_year,5_years',
        ]);

        $store = $data['store_id'] ? Store::find($data['store_id']) : null;

        $voucher = $this->voucherService->createManual(
            $data['amount'],
            auth()->user(),
            $store,
            $data['validity']
        );

        return redirect()->route('vouchers.show', $voucher)
            ->with('success', __('messages.voucher.created', ['code' => $voucher->code]));
    }

    public function cancel(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($voucher->status !== 'active') {
            return back()->with('error', __('messages.voucher.cannot_cancel'));
        }

        $this->voucherService->cancel($voucher, auth()->user(), $data['reason']);

        return redirect()->route('vouchers.show', $voucher)
            ->with('success', __('messages.voucher.cancelled'));
    }

    public function export(Request $request)
    {
        $query = Voucher::with(['createdAtStore', 'usedAtStore']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('store_id')) {
            $query->where('created_at_store_id', $request->store_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $vouchers = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vouchers_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($vouchers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Code', 'Amount', 'Status', 'Source', 'Created At', 'Store', 'Expires At', 'Used At', 'Used Store']);

            foreach ($vouchers as $voucher) {
                fputcsv($file, [
                    $voucher->code,
                    $voucher->amount,
                    $voucher->status,
                    $voucher->source_type,
                    $voucher->created_at->format('Y-m-d H:i'),
                    $voucher->createdAtStore?->name ?? '-',
                    $voucher->expires_at->format('Y-m-d'),
                    $voucher->used_at?->format('Y-m-d H:i') ?? '-',
                    $voucher->usedAtStore?->name ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
