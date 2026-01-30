<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserSalary;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\Leave;
use App\Models\UserSchedule;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffController extends Controller
{
    public function create()
    {
        $stores = Store::orderBy('name')->get();
        return view('staff.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'hire_date' => 'nullable|date',
            'store_id' => 'nullable|exists:stores,id',
            'base_salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'is_staff' => true,
            'contract_status' => 'active',
            'password' => bcrypt(str()->random(32)), // Random password (no login)
        ]);

        // Create initial salary if provided
        if (!empty($validated['base_salary']) && $validated['base_salary'] > 0) {
            $user->salaries()->create([
                'base_salary' => $validated['base_salary'],
                'currency' => $validated['currency'] ?? 'USD',
                'effective_from' => $validated['hire_date'] ?? now(),
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('staff.show', $user)
            ->with('success', __('messages.staff.employee_created'));
    }

    public function terminate(Request $request, User $user)
    {
        $validated = $request->validate([
            'contract_end_date' => 'required|date',
            'termination_reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'contract_status' => 'terminated',
            'contract_end_date' => $validated['contract_end_date'],
            'termination_reason' => $validated['termination_reason'],
        ]);

        return redirect()
            ->route('staff.show', $user)
            ->with('success', __('messages.staff.contract_terminated'));
    }

    public function reactivate(User $user)
    {
        $user->update([
            'contract_status' => 'active',
            'contract_end_date' => null,
            'termination_reason' => null,
        ]);

        return redirect()
            ->route('staff.show', $user)
            ->with('success', __('messages.staff.contract_reactivated'));
    }

    public function index(Request $request)
    {
        $currentMonth = now()->format('Y-m');
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $query = User::with(['store', 'currentSalary'])
            ->where('is_staff', true);

        // Contract status filter (default: active)
        $contractStatus = $request->get('status', 'active');
        if ($contractStatus !== 'all') {
            $query->where('contract_status', $contractStatus);
        }

        // Search filter
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Store filter
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $users = $query->orderBy('name')->paginate(20);
        $stores = Store::orderBy('name')->get();

        // Calculate payroll data for each user (only for active employees)
        foreach ($users as $user) {
            if ($user->contract_status === 'active') {
                $user->payroll_calculated = $this->calculatePayrollForUser($user, $currentMonth, $monthStart, $monthEnd);
            } else {
                $user->payroll_calculated = ['base_salary' => 0, 'net_amount' => 0, 'deductions' => 0, 'is_paid' => false, 'currency' => 'USD'];
            }
        }

        // Count active staff pending payment this month
        $pendingPaymentsCount = User::where('is_staff', true)
            ->where('contract_status', 'active')
            ->whereHas('currentSalary', function ($q) {
                $q->where('base_salary', '>', 0);
            })
            ->whereDoesntHave('salaryPayments', function ($q) use ($currentMonth) {
                $q->where('period', $currentMonth);
            })
            ->count();

        return view('staff.index', compact('users', 'stores', 'currentMonth', 'pendingPaymentsCount', 'contractStatus'));
    }

    private function calculatePayrollForUser(User $user, string $currentMonth, $monthStart, $monthEnd): array
    {
        $baseSalary = $user->currentSalary?->base_salary ?? 0;
        $currency = $user->currentSalary?->currency ?? 'USD';

        if ($baseSalary == 0) {
            return [
                'base_salary' => 0,
                'currency' => $currency,
                'net_amount' => 0,
                'is_paid' => false,
                'deductions' => 0,
            ];
        }

        // Check if already paid
        $isPaid = $user->salaryPayments()->where('period', $currentMonth)->exists();

        // Calculate unjustified days
        $unjustifiedDays = $user->leaves()
            ->where('type', 'unjustified')
            ->where('status', 'approved')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                  ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
            })
            ->get()
            ->sum(function ($leave) use ($monthStart, $monthEnd) {
                $start = $leave->start_date->max($monthStart);
                $end = $leave->end_date->min($monthEnd);
                return $start->diffInDays($end) + 1;
            });

        // Get approved advances
        $advancesTotal = $user->salaryAdvances()
            ->where('status', 'approved')
            ->sum('amount');

        $dailyRate = round($baseSalary / 30, 2);
        $absenceDeduction = $unjustifiedDays * $dailyRate;
        $totalDeductions = $absenceDeduction + $advancesTotal;
        $netAmount = $baseSalary - $totalDeductions;

        return [
            'base_salary' => $baseSalary,
            'currency' => $currency,
            'daily_rate' => $dailyRate,
            'unjustified_days' => $unjustifiedDays,
            'absence_deduction' => $absenceDeduction,
            'advances_total' => $advancesTotal,
            'deductions' => $totalDeductions,
            'net_amount' => $netAmount,
            'is_paid' => $isPaid,
        ];
    }

    public function show(Request $request, User $user)
    {
        $user->load([
            'store',
            'documents.uploader',
            'salaries.creator',
            'salaryAdvances.approver',
            'leaves.approver',
            'schedules',
            'salaryPayments.payer',
            'salaryPayments.store',
        ]);

        // Initialize schedules if not exist
        $schedules = [];
        for ($day = 0; $day <= 6; $day++) {
            $schedule = $user->schedules->firstWhere('day_of_week', $day);
            $schedules[$day] = $schedule ?? new UserSchedule([
                'day_of_week' => $day,
                'is_working_day' => false,
                'start_time' => null,
                'end_time' => null,
            ]);
        }

        // Calculate total weekly hours
        $totalHours = collect($schedules)->sum(fn($s) => $s->getHoursWorked());

        // Calculate leave balances (simple calculation: approved leaves by type this year)
        $yearStart = now()->startOfYear();
        $leaveBalances = [
            'vacation' => $user->leaves()
                ->where('type', 'vacation')
                ->where('status', 'approved')
                ->where('start_date', '>=', $yearStart)
                ->get()
                ->sum(fn($l) => $l->getDaysCount()),
            'sick' => $user->leaves()
                ->where('type', 'sick')
                ->where('status', 'approved')
                ->where('start_date', '>=', $yearStart)
                ->get()
                ->sum(fn($l) => $l->getDaysCount()),
            'dayoff' => $user->leaves()
                ->where('type', 'dayoff')
                ->where('status', 'approved')
                ->where('start_date', '>=', $yearStart)
                ->get()
                ->sum(fn($l) => $l->getDaysCount()),
        ];

        // Get pending advances total (approved but not yet deducted)
        $pendingAdvancesTotal = $user->salaryAdvances()
            ->where('status', 'approved')
            ->sum('amount');

        // Calculate payroll data for current month
        $payrollMonth = $request->get('payroll_month', now()->format('Y-m'));
        $payrollStart = \Carbon\Carbon::parse($payrollMonth . '-01')->startOfMonth();
        $payrollEnd = $payrollStart->copy()->endOfMonth();

        // Get unjustified absences for the selected month
        $unjustifiedDays = $user->leaves()
            ->where('type', 'unjustified')
            ->where('status', 'approved')
            ->where(function ($q) use ($payrollStart, $payrollEnd) {
                $q->whereBetween('start_date', [$payrollStart, $payrollEnd])
                  ->orWhereBetween('end_date', [$payrollStart, $payrollEnd])
                  ->orWhere(function ($q2) use ($payrollStart, $payrollEnd) {
                      $q2->where('start_date', '<=', $payrollStart)
                         ->where('end_date', '>=', $payrollEnd);
                  });
            })
            ->get()
            ->sum(function ($leave) use ($payrollStart, $payrollEnd) {
                // Calculate days within the month only
                $start = $leave->start_date->max($payrollStart);
                $end = $leave->end_date->min($payrollEnd);
                return $start->diffInDays($end) + 1;
            });

        // Suggested daily rate (base salary / 30)
        $baseSalary = $user->currentSalary?->base_salary ?? 0;
        $suggestedDailyRate = $baseSalary > 0 ? round($baseSalary / 30, 2) : 0;

        $payrollData = [
            'month' => $payrollMonth,
            'base_salary' => $baseSalary,
            'currency' => $user->currentSalary?->currency ?? 'USD',
            'unjustified_days' => $unjustifiedDays,
            'suggested_daily_rate' => $suggestedDailyRate,
            'advances_total' => $pendingAdvancesTotal,
        ];

        $tab = $request->get('tab', 'info');

        return view('staff.show', compact(
            'user',
            'schedules',
            'totalHours',
            'leaveBalances',
            'pendingAdvancesTotal',
            'payrollData',
            'tab'
        ));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'hire_date' => 'nullable|date',
        ]);

        $user->update($validated);

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'info'])
            ->with('success', __('messages.staff.updated'));
    }

    public function uploadDocument(Request $request, User $user)
    {
        $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:photo,contract,id_card,other',
        ]);

        $file = $request->file('document');
        $path = $file->store('staff-documents/' . $user->id, 'public');

        $user->documents()->create([
            'type' => $request->type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'documents'])
            ->with('success', __('messages.staff.document_uploaded'));
    }

    public function deleteDocument(UserDocument $document)
    {
        $userId = $document->user_id;

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return redirect()
            ->route('staff.show', ['user' => $userId, 'tab' => 'documents'])
            ->with('success', __('messages.staff.document_deleted'));
    }

    public function updateSalary(Request $request, User $user)
    {
        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'effective_from' => 'required|date',
        ]);

        $user->salaries()->create([
            'base_salary' => $validated['base_salary'],
            'currency' => $validated['currency'],
            'effective_from' => $validated['effective_from'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'salary'])
            ->with('success', __('messages.staff.salary_updated'));
    }

    public function storeAdvance(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $user->salaryAdvances()->create([
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'salary'])
            ->with('success', __('messages.staff.advance_created'));
    }

    public function approveAdvance(Request $request, SalaryAdvance $advance)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'store_id' => 'required_if:action,approve|exists:stores,id',
        ]);

        if ($validated['action'] === 'approve') {
            // Create financial transaction
            $account = FinancialAccount::where('store_id', $validated['store_id'])
                ->where('name', 'like', '%Salary%')
                ->orWhere('name', 'like', '%salary%')
                ->orWhere('name', 'like', '%Salaire%')
                ->first();

            // If no salary account exists, use any expense account
            if (!$account) {
                $account = FinancialAccount::where('store_id', $validated['store_id'])
                    ->where('type', 'expense')
                    ->first();
            }

            $transaction = null;
            if ($account) {
                $transaction = FinancialTransaction::create([
                    'store_id' => $validated['store_id'],
                    'account_id' => $account->id,
                    'amount' => $advance->amount,
                    'currency' => 'USD',
                    'direction' => 'debit',
                    'balance_before' => $account->balance ?? 0,
                    'balance_after' => ($account->balance ?? 0) - $advance->amount,
                    'label' => 'Salary Advance - ' . $advance->user->name,
                    'description' => $advance->reason,
                    'status' => 'completed',
                    'transaction_date' => now(),
                    'user_id' => auth()->id(),
                ]);
            }

            $advance->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'financial_transaction_id' => $transaction?->id,
            ]);

            $message = __('messages.staff.advance_approved');
        } else {
            $advance->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $message = __('messages.staff.advance_rejected');
        }

        return redirect()
            ->route('staff.show', ['user' => $advance->user_id, 'tab' => 'salary'])
            ->with('success', $message);
    }

    public function storeLeave(Request $request, User $user)
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,dayoff,sick,unjustified',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $user->leaves()->create([
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'leaves'])
            ->with('success', __('messages.staff.leave_created'));
    }

    public function approveLeave(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $leave->update([
            'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $message = $validated['action'] === 'approve'
            ? __('messages.staff.leave_approved')
            : __('messages.staff.leave_rejected');

        return redirect()
            ->route('staff.show', ['user' => $leave->user_id, 'tab' => 'leaves'])
            ->with('success', $message);
    }

    public function updateSchedule(Request $request, User $user)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.is_working_day' => 'boolean',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
        ]);

        foreach ($validated['schedules'] as $scheduleData) {
            $user->schedules()->updateOrCreate(
                ['day_of_week' => $scheduleData['day_of_week']],
                [
                    'is_working_day' => $scheduleData['is_working_day'] ?? false,
                    'start_time' => $scheduleData['is_working_day'] ?? false ? $scheduleData['start_time'] : null,
                    'end_time' => $scheduleData['is_working_day'] ?? false ? $scheduleData['end_time'] : null,
                ]
            );
        }

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'schedule'])
            ->with('success', __('messages.staff.schedule_updated'));
    }

    public function storeSalaryPayment(Request $request, User $user)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'base_salary' => 'required|numeric|min:0',
            'daily_rate' => 'required|numeric|min:0',
            'unjustified_days' => 'required|integer|min:0',
            'advances_deduction' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if payment already exists for this period
        $existingPayment = $user->salaryPayments()->where('period', $validated['period'])->first();
        if ($existingPayment) {
            return redirect()
                ->route('staff.show', ['user' => $user, 'tab' => 'salary'])
                ->with('error', __('messages.staff.payment_already_exists'));
        }

        $absenceDeduction = $validated['unjustified_days'] * $validated['daily_rate'];

        DB::transaction(function () use ($user, $validated, $absenceDeduction) {
            // Create financial transaction
            $account = FinancialAccount::where('store_id', $validated['store_id'])
                ->where(function ($q) {
                    $q->where('name', 'like', '%Salary%')
                      ->orWhere('name', 'like', '%salary%')
                      ->orWhere('name', 'like', '%Salaire%');
                })
                ->first();

            if (!$account) {
                $account = FinancialAccount::where('store_id', $validated['store_id'])
                    ->where('type', 'expense')
                    ->first();
            }

            $transaction = null;
            if ($account) {
                $transaction = FinancialTransaction::create([
                    'store_id' => $validated['store_id'],
                    'account_id' => $account->id,
                    'amount' => $validated['net_amount'],
                    'currency' => $validated['currency'],
                    'direction' => 'debit',
                    'balance_before' => $account->balance ?? 0,
                    'balance_after' => ($account->balance ?? 0) - $validated['net_amount'],
                    'label' => 'Salary Payment - ' . $user->name . ' (' . $validated['period'] . ')',
                    'description' => $validated['notes'],
                    'status' => 'completed',
                    'transaction_date' => now(),
                    'user_id' => auth()->id(),
                ]);
            }

            // Create salary payment record
            $user->salaryPayments()->create([
                'period' => $validated['period'],
                'base_salary' => $validated['base_salary'],
                'daily_rate' => $validated['daily_rate'],
                'unjustified_days' => $validated['unjustified_days'],
                'absence_deduction' => $absenceDeduction,
                'advances_deduction' => $validated['advances_deduction'],
                'net_amount' => $validated['net_amount'],
                'currency' => $validated['currency'],
                'notes' => $validated['notes'],
                'paid_by' => auth()->id(),
                'store_id' => $validated['store_id'],
                'financial_transaction_id' => $transaction?->id,
            ]);

            // Mark advances as deducted if there were any
            if ($validated['advances_deduction'] > 0) {
                $user->salaryAdvances()
                    ->where('status', 'approved')
                    ->update(['status' => 'deducted']);
            }
        });

        return redirect()
            ->route('staff.show', ['user' => $user, 'tab' => 'payroll'])
            ->with('success', __('messages.staff.payment_recorded'));
    }

    public function showPayment(SalaryPayment $payment)
    {
        $payment->load(['user', 'payer', 'store', 'financialTransaction']);

        return view('staff.payment-detail', compact('payment'));
    }

    public function bulkPayment(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $currentMonth = now()->format('Y-m');
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $paidCount = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($validated, $currentMonth, $monthStart, $monthEnd, &$paidCount, &$totalAmount) {
            $users = User::with(['currentSalary', 'salaryAdvances', 'leaves'])
                ->whereIn('id', $validated['user_ids'])
                ->whereHas('currentSalary')
                ->whereDoesntHave('salaryPayments', function ($q) use ($currentMonth) {
                    $q->where('period', $currentMonth);
                })
                ->get();

            foreach ($users as $user) {
                $payrollData = $this->calculatePayrollForUser($user, $currentMonth, $monthStart, $monthEnd);

                if ($payrollData['base_salary'] <= 0) {
                    continue;
                }

                // Create financial transaction
                $account = FinancialAccount::where('store_id', $validated['store_id'])
                    ->where(function ($q) {
                        $q->where('name', 'like', '%Salary%')
                          ->orWhere('name', 'like', '%salary%')
                          ->orWhere('name', 'like', '%Salaire%');
                    })
                    ->first();

                if (!$account) {
                    $account = FinancialAccount::where('store_id', $validated['store_id'])
                        ->where('type', 'expense')
                        ->first();
                }

                $transaction = null;
                if ($account) {
                    $transaction = FinancialTransaction::create([
                        'store_id' => $validated['store_id'],
                        'account_id' => $account->id,
                        'amount' => $payrollData['net_amount'],
                        'currency' => $payrollData['currency'],
                        'direction' => 'debit',
                        'balance_before' => $account->balance ?? 0,
                        'balance_after' => ($account->balance ?? 0) - $payrollData['net_amount'],
                        'label' => 'Salary Payment - ' . $user->name . ' (' . $currentMonth . ')',
                        'description' => 'Bulk payment',
                        'status' => 'completed',
                        'transaction_date' => now(),
                        'user_id' => auth()->id(),
                    ]);
                }

                // Create salary payment
                $user->salaryPayments()->create([
                    'period' => $currentMonth,
                    'base_salary' => $payrollData['base_salary'],
                    'daily_rate' => $payrollData['daily_rate'],
                    'unjustified_days' => $payrollData['unjustified_days'],
                    'absence_deduction' => $payrollData['absence_deduction'],
                    'advances_deduction' => $payrollData['advances_total'],
                    'net_amount' => $payrollData['net_amount'],
                    'currency' => $payrollData['currency'],
                    'notes' => 'Bulk payment',
                    'paid_by' => auth()->id(),
                    'store_id' => $validated['store_id'],
                    'financial_transaction_id' => $transaction?->id,
                ]);

                // Mark advances as deducted
                if ($payrollData['advances_total'] > 0) {
                    $user->salaryAdvances()
                        ->where('status', 'approved')
                        ->update(['status' => 'deducted']);
                }

                $paidCount++;
                $totalAmount += $payrollData['net_amount'];
            }
        });

        return redirect()
            ->route('staff.index')
            ->with('success', __('messages.staff.bulk_payment_success', [
                'count' => $paidCount,
                'amount' => number_format($totalAmount, 2),
            ]));
    }

    public function downloadPayslip(SalaryPayment $payment)
    {
        $payment->load(['user.store', 'payer', 'store']);

        $pdf = Pdf::loadView('staff.payslip', compact('payment'));

        $filename = 'payslip_' . $payment->user->name . '_' . $payment->period . '.pdf';
        $filename = str_replace(' ', '_', $filename);

        return $pdf->download($filename);
    }
}
