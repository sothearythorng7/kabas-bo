<?php

namespace App\Http\Controllers;

use App\Models\StaffMember;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserSalary;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\UserSchedule;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\Store;
use App\Models\Reseller;
use App\Models\EmployeeCommission;
use App\Models\CommissionCalculation;
use App\Models\SalaryAdjustment;
use App\Models\Sale;
use App\Services\LeaveQuotaService;
use App\Services\CommissionService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffController extends Controller
{
    protected LeaveQuotaService $leaveQuotaService;
    protected CommissionService $commissionService;
    protected PayrollService $payrollService;

    public function __construct(
        LeaveQuotaService $leaveQuotaService,
        CommissionService $commissionService,
        PayrollService $payrollService
    ) {
        $this->leaveQuotaService = $leaveQuotaService;
        $this->commissionService = $commissionService;
        $this->payrollService = $payrollService;
    }
    public function create()
    {
        $stores = Store::orderBy('name')->get();
        $roles = Role::all();
        $unlinkedUsers = User::whereDoesntHave('staffMember')->orderBy('name')->get();
        return view('staff.create', compact('stores', 'roles', 'unlinkedUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'hire_date' => 'nullable|date',
            'store_id' => 'nullable|exists:stores,id',
            'base_salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'account_mode' => 'nullable|in:none,create,link',
            'user_id' => 'nullable|exists:users,id',
            'user_email' => 'nullable|email|unique:users,email',
            'user_password' => 'nullable|string|min:6',
            'user_role' => 'nullable|string|exists:roles,name',
            'pin_code' => 'nullable|digits:6',
        ]);

        $userId = null;
        $accountMode = $validated['account_mode'] ?? 'none';

        if ($accountMode === 'create' && !empty($validated['user_email']) && !empty($validated['user_password'])) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['user_email'],
                'password' => Hash::make($validated['user_password']),
                'store_id' => $validated['store_id'] ?? null,
                'pin_code' => $validated['pin_code'] ?? null,
                'locale' => 'fr',
            ]);
            if (!empty($validated['user_role'])) {
                $user->assignRole($validated['user_role']);
            }
            $userId = $user->id;
        } elseif ($accountMode === 'link' && !empty($validated['user_id'])) {
            $userId = $validated['user_id'];
        }

        $staffMember = StaffMember::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'contract_status' => 'active',
            'user_id' => $userId,
        ]);

        // Create initial salary if provided
        if (!empty($validated['base_salary']) && $validated['base_salary'] > 0) {
            $staffMember->salaries()->create([
                'base_salary' => $validated['base_salary'],
                'currency' => $validated['currency'] ?? 'USD',
                'effective_from' => $validated['hire_date'] ?? now(),
                'created_by' => auth()->id(),
            ]);
        }

        // Initialize leave quotas for the new employee
        $this->leaveQuotaService->initializeQuotasForEmployee($staffMember);

        return redirect()
            ->route('staff.show', $staffMember)
            ->with('success', __('messages.staff.employee_created'));
    }

    public function terminate(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'contract_end_date' => 'required|date',
            'termination_reason' => 'nullable|string|max:500',
        ]);

        $staffMember->update([
            'contract_status' => 'terminated',
            'contract_end_date' => $validated['contract_end_date'],
            'termination_reason' => $validated['termination_reason'],
        ]);

        return redirect()
            ->route('staff.show', $staffMember)
            ->with('success', __('messages.staff.contract_terminated'));
    }

    public function reactivate(StaffMember $staffMember)
    {
        $staffMember->update([
            'contract_status' => 'active',
            'contract_end_date' => null,
            'termination_reason' => null,
        ]);

        return redirect()
            ->route('staff.show', $staffMember)
            ->with('success', __('messages.staff.contract_reactivated'));
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'list');
        $stores = Store::orderBy('name')->get();

        $currentMonth = now()->format('Y-m');
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Count pending payments (needed for all tabs)
        $pendingPaymentsCount = StaffMember::where('contract_status', 'active')
            ->whereHas('currentSalary', function ($q) {
                $q->where('base_salary', '>', 0);
            })
            ->whereDoesntHave('salaryPayments', function ($q) use ($currentMonth) {
                $q->where('period', $currentMonth);
            })
            ->count();

        // Initialize variables for all tabs
        $staffMembers = collect();
        $contractStatus = 'active';
        $planning = [];
        $planningDays = [];
        $planningSummary = [];
        $planningMonth = now()->format('Y-m');
        $planningStoreId = null;
        $performances = collect();
        $perfTotals = [];
        $topPerformers = collect();
        $perfPeriod = now()->format('Y-m');
        $perfStoreId = null;

        if ($tab === 'list') {
            // List tab data
            $query = StaffMember::with(['store', 'currentSalary'])
                ->orderBy('name');

            $contractStatus = $request->get('status', 'active');
            if ($contractStatus !== 'all') {
                $query->where('contract_status', $contractStatus);
            }

            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            $staffMembers = $query->paginate(20);

            foreach ($staffMembers as $sm) {
                if ($sm->contract_status === 'active') {
                    $payroll = $this->payrollService->calculatePayrollForUser($sm, $currentMonth);
                    $payroll['deductions'] = $payroll['total_deductions'];
                    $sm->payroll_calculated = $payroll;
                    $sm->commission_summary = $this->commissionService->getCommissionSummary($sm, $currentMonth);
                } else {
                    $sm->payroll_calculated = ['base_salary' => 0, 'net_amount' => 0, 'deductions' => 0, 'total_deductions' => 0, 'total_additions' => 0, 'is_paid' => false, 'currency' => 'USD', 'overtime_amount' => 0, 'bonus_amount' => 0, 'penalty_amount' => 0, 'commission_amount' => 0, 'gross_salary' => 0, 'daily_rate' => 0, 'unjustified_days' => 0, 'absence_deduction' => 0, 'advances_total' => 0];
                    $sm->commission_summary = ['details' => collect(), 'total_commission' => 0];
                }
            }

        } elseif ($tab === 'planning') {
            // Planning tab data
            $planningStoreId = $request->get('store_id');
            $planningMonth = $request->get('month', now()->format('Y-m'));

            $planningMonthStart = \Carbon\Carbon::parse($planningMonth . '-01')->startOfMonth();
            $planningMonthEnd = $planningMonthStart->copy()->endOfMonth();
            $daysInMonth = $planningMonthStart->daysInMonth;

            // Build days array
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = $planningMonthStart->copy()->day($i);
                $planningDays[] = [
                    'day' => $i,
                    'date' => $date->format('Y-m-d'),
                    'dow' => $date->dayOfWeek,
                    'is_weekend' => $date->isWeekend(),
                    'label' => $date->translatedFormat('D'),
                ];
            }

            // Get employees
            $query = StaffMember::with(['schedules', 'store', 'leaves' => function ($q) use ($planningMonthStart, $planningMonthEnd) {
                $q->whereIn('status', ['approved', 'pending'])
                    ->where(function ($q2) use ($planningMonthStart, $planningMonthEnd) {
                        $q2->whereBetween('start_date', [$planningMonthStart, $planningMonthEnd])
                            ->orWhereBetween('end_date', [$planningMonthStart, $planningMonthEnd])
                            ->orWhere(function ($q3) use ($planningMonthStart, $planningMonthEnd) {
                                $q3->where('start_date', '<=', $planningMonthStart)
                                    ->where('end_date', '>=', $planningMonthEnd);
                            });
                    });
            }])
                ->where('contract_status', 'active')
                ->orderBy('name');

            if ($planningStoreId) {
                $query->where('store_id', $planningStoreId);
            }

            $employees = $query->get();

            // Build planning grid
            foreach ($employees as $employee) {
                $employeeDays = [];
                $workingDays = $employee->schedules->where('is_working_day', true)->pluck('day_of_week')->toArray();

                foreach ($planningDays as $day) {
                    $date = $day['date'];
                    $dayOfWeek = $day['dow'];

                    $leave = $employee->leaves->first(function ($l) use ($date) {
                        return $l->start_date->format('Y-m-d') <= $date && $l->end_date->format('Y-m-d') >= $date;
                    });

                    if ($leave) {
                        $employeeDays[$day['day']] = [
                            'status' => 'absent',
                            'type' => $leave->type,
                            'leave_status' => $leave->status,
                            'reason' => $leave->reason,
                        ];
                    } elseif (in_array($dayOfWeek, $workingDays)) {
                        $employeeDays[$day['day']] = ['status' => 'present', 'type' => null];
                    } else {
                        $employeeDays[$day['day']] = ['status' => 'off', 'type' => null];
                    }
                }

                $planning[] = [
                    'employee' => $employee,
                    'days' => $employeeDays,
                    'total_present' => collect($employeeDays)->where('status', 'present')->count(),
                    'total_absent' => collect($employeeDays)->where('status', 'absent')->count(),
                ];
            }

            $planningSummary = [
                'total_employees' => count($planning),
                'days_in_month' => $daysInMonth,
            ];

        } elseif ($tab === 'performance') {
            // Performance tab data
            $perfStoreId = $request->get('store_id');
            $perfPeriod = $request->get('period', now()->format('Y-m'));

            $perfPeriodStart = \Carbon\Carbon::parse($perfPeriod . '-01')->startOfMonth();
            $perfPeriodEnd = $perfPeriodStart->copy()->endOfMonth();

            // Get sellers (staff members linked to users with SELLER role)
            $query = StaffMember::with(['store', 'schedules', 'user.roles'])
                ->where('contract_status', 'active')
                ->whereHas('user', function ($q) {
                    $q->whereHas('roles', fn($r) => $r->whereIn('name', ['SELLER', 'seller']));
                });

            if ($perfStoreId) {
                $query->where('store_id', $perfStoreId);
            }

            $employees = $query->get();

            $performances = $employees->map(function ($staffMember) use ($perfPeriodStart, $perfPeriodEnd) {
                // Get shifts from linked user
                $userShifts = $staffMember->user
                    ? $staffMember->user->shifts()->whereBetween('started_at', [$perfPeriodStart, $perfPeriodEnd])->get()
                    : collect();
                $shiftsWorked = $userShifts->count();

                $shiftIds = $userShifts->pluck('id')->toArray();
                $staffSales = !empty($shiftIds) ? Sale::whereIn('shift_id', $shiftIds)->get() : collect();
                $totalSales = Sale::sumRealRevenue($staffSales);
                $salesCount = $staffSales->count();

                $workingDays = $staffMember->schedules->where('is_working_day', true)->count();
                $totalWorkingDaysInMonth = $workingDays * 4;

                $absences = $staffMember->leaves()
                    ->where('status', 'approved')
                    ->where(function ($q) use ($perfPeriodStart, $perfPeriodEnd) {
                        $q->whereBetween('start_date', [$perfPeriodStart, $perfPeriodEnd])
                            ->orWhereBetween('end_date', [$perfPeriodStart, $perfPeriodEnd]);
                    })
                    ->get()
                    ->sum(fn($leave) => $leave->getDaysCount());

                $attendanceRate = $totalWorkingDaysInMonth > 0
                    ? round((($totalWorkingDaysInMonth - $absences) / $totalWorkingDaysInMonth) * 100, 1)
                    : 0;

                return [
                    'staffMember' => $staffMember,
                    'working_days_scheduled' => $totalWorkingDaysInMonth,
                    'absences' => $absences,
                    'shifts_worked' => $shiftsWorked,
                    'total_sales' => $totalSales,
                    'sales_count' => $salesCount,
                    'attendance_rate' => max(0, min(100, $attendanceRate)),
                ];
            })->sortByDesc('total_sales');

            $perfTotals = [
                'total_employees' => $employees->count(),
                'total_absences' => $performances->sum('absences'),
                'total_sales' => $performances->sum('total_sales'),
                'total_sales_count' => $performances->sum('sales_count'),
                'avg_attendance' => $performances->avg('attendance_rate') ?? 0,
            ];

            $topPerformers = $performances->take(5);
        }

        return view('staff.index', compact(
            'tab',
            'stores',
            'currentMonth',
            'pendingPaymentsCount',
            // List tab
            'staffMembers',
            'contractStatus',
            // Planning tab
            'planning',
            'planningDays',
            'planningSummary',
            'planningMonth',
            'planningStoreId',
            // Performance tab
            'performances',
            'perfTotals',
            'topPerformers',
            'perfPeriod',
            'perfStoreId'
        ));
    }

    public function show(Request $request, StaffMember $staffMember)
    {
        $staffMember->load([
            'store',
            'user.roles',
            'documents.uploader',
            'salaries.creator',
            'salaryAdvances.approver',
            'leaves.approver',
            'schedules',
            'salaryPayments.payer',
            'salaryPayments.store',
            'leaveQuotas',
            'employeeCommissions',
            'salaryAdjustments.approver',
        ]);

        // Initialize schedules if not exist
        $schedules = [];
        for ($day = 0; $day <= 6; $day++) {
            $schedule = $staffMember->schedules->firstWhere('day_of_week', $day);
            $schedules[$day] = $schedule ?? new UserSchedule([
                'day_of_week' => $day,
                'is_working_day' => false,
                'start_time' => null,
                'end_time' => null,
            ]);
        }

        // Calculate total weekly hours
        $totalHours = collect($schedules)->sum(fn($s) => $s->getHoursWorked());

        // Get quota balances using the service
        $quotaBalances = $this->leaveQuotaService->getQuotaBalances($staffMember);

        // Legacy leave balances for backward compatibility
        $leaveBalances = [
            'vacation' => $quotaBalances['vacation']['used'] ?? 0,
            'sick' => $quotaBalances['sick']['used'] ?? 0,
            'dayoff' => $quotaBalances['dayoff']['used'] ?? 0,
        ];

        // Get pending advances total (approved but not yet deducted)
        $pendingAdvancesTotal = $staffMember->salaryAdvances()
            ->where('status', 'approved')
            ->sum('amount');

        // Calculate payroll data for current month using the service
        $payrollMonth = $request->get('payroll_month', now()->format('Y-m'));
        $payrollData = $this->payrollService->calculatePayrollForUser($staffMember, $payrollMonth);
        $payrollData['month'] = $payrollMonth;
        $payrollData['suggested_daily_rate'] = $payrollData['daily_rate'];
        $payrollData['advances_total'] = $payrollData['advances_total'] ?? $pendingAdvancesTotal;

        // Get commission summary
        $commissionSummary = $this->commissionService->getCommissionSummary($staffMember, $payrollMonth);

        // Get pending adjustments
        $pendingAdjustments = $this->payrollService->getPendingAdjustments($staffMember, $payrollMonth);

        // Get stores and resellers for commission form
        $stores = Store::orderBy('name')->get();
        $resellers = Reseller::orderBy('name')->get();

        $tab = $request->get('tab', 'info');

        // Performance data
        $performanceData = null;
        $performancePeriod = $request->get('perf_period', now()->format('Y-m'));

        if ($tab === 'performance' || $request->has('perf_period')) {
            $performanceData = $this->calculateStaffMemberPerformance($staffMember, $performancePeriod);
        }

        // User planning data
        $userPlanningData = null;
        $userPlanningMonth = $request->get('planning_month', now()->format('Y-m'));

        if ($tab === 'planning' || $request->has('planning_month')) {
            $userPlanningData = $this->calculateStaffMemberPlanning($staffMember, $userPlanningMonth);
        }

        return view('staff.show', compact(
            'staffMember',
            'schedules',
            'totalHours',
            'leaveBalances',
            'quotaBalances',
            'pendingAdvancesTotal',
            'payrollData',
            'commissionSummary',
            'pendingAdjustments',
            'stores',
            'resellers',
            'tab',
            'performanceData',
            'performancePeriod',
            'userPlanningData',
            'userPlanningMonth'
        ));
    }

    public function update(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'hire_date' => 'nullable|date',
        ]);

        $staffMember->update($validated);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'info'])
            ->with('success', __('messages.staff.updated'));
    }

    public function uploadDocument(Request $request, StaffMember $staffMember)
    {
        $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:photo,contract,id_card,other',
        ]);

        $file = $request->file('document');
        $path = $file->store('staff-documents/' . $staffMember->id, 'public');

        $staffMember->documents()->create([
            'type' => $request->type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'documents'])
            ->with('success', __('messages.staff.document_uploaded'));
    }

    public function deleteDocument(UserDocument $document)
    {
        $staffMemberId = $document->staff_member_id;

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMemberId, 'tab' => 'documents'])
            ->with('success', __('messages.staff.document_deleted'));
    }

    public function updateSalary(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'effective_from' => 'required|date',
        ]);

        $staffMember->salaries()->create([
            'base_salary' => $validated['base_salary'],
            'currency' => $validated['currency'],
            'effective_from' => $validated['effective_from'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'salary'])
            ->with('success', __('messages.staff.salary_updated'));
    }

    public function storeAdvance(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $staffMember->salaryAdvances()->create([
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'salary'])
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
                    'label' => 'Salary Advance - ' . $advance->staffMember->name,
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
            ->route('staff.show', ['staffMember' => $advance->staff_member_id, 'tab' => 'salary'])
            ->with('success', $message);
    }

    public function storeLeave(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,dayoff,sick,unjustified',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $staffMember->leaves()->create([
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'leaves'])
            ->with('success', __('messages.staff.leave_created'));
    }

    public function approveLeave(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        if ($validated['action'] === 'approve') {
            // Validate quota before approving
            $validation = $this->leaveQuotaService->validateLeaveRequest($leave);

            if (!$validation['allowed']) {
                return redirect()
                    ->route('staff.show', ['staffMember' => $leave->staff_member_id, 'tab' => 'leaves'])
                    ->with('error', $validation['message']);
            }

            $leave->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Link leave to quota
            $this->leaveQuotaService->linkLeaveToQuota($leave);

            $message = __('messages.staff.leave_approved');
        } else {
            $leave->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $message = __('messages.staff.leave_rejected');
        }

        return redirect()
            ->route('staff.show', ['staffMember' => $leave->staff_member_id, 'tab' => 'leaves'])
            ->with('success', $message);
    }

    public function updateSchedule(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.is_working_day' => 'boolean',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
        ]);

        foreach ($validated['schedules'] as $scheduleData) {
            $staffMember->schedules()->updateOrCreate(
                ['day_of_week' => $scheduleData['day_of_week']],
                [
                    'is_working_day' => $scheduleData['is_working_day'] ?? false,
                    'start_time' => $scheduleData['is_working_day'] ?? false ? $scheduleData['start_time'] : null,
                    'end_time' => $scheduleData['is_working_day'] ?? false ? $scheduleData['end_time'] : null,
                ]
            );
        }

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'schedule'])
            ->with('success', __('messages.staff.schedule_updated'));
    }

    public function storeSalaryPayment(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'base_salary' => 'required|numeric|min:0',
            'daily_rate' => 'required|numeric|min:0',
            'unjustified_days' => 'required|integer|min:0',
            'advances_deduction' => 'required|numeric|min:0',
            'overtime_amount' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'net_amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if payment already exists for this period
        $existingPayment = $staffMember->salaryPayments()->where('period', $validated['period'])->first();
        if ($existingPayment) {
            return redirect()
                ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'salary'])
                ->with('error', __('messages.staff.payment_already_exists'));
        }

        $absenceDeduction = $validated['unjustified_days'] * $validated['daily_rate'];
        $overtimeAmount = $validated['overtime_amount'] ?? 0;
        $bonusAmount = $validated['bonus_amount'] ?? 0;
        $penaltyAmount = $validated['penalty_amount'] ?? 0;
        $commissionAmount = $validated['commission_amount'] ?? 0;
        $grossSalary = $validated['base_salary'] + $overtimeAmount + $bonusAmount + $commissionAmount;

        DB::transaction(function () use ($staffMember, $validated, $absenceDeduction, $overtimeAmount, $bonusAmount, $penaltyAmount, $commissionAmount, $grossSalary) {
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
                    'label' => 'Salary Payment - ' . $staffMember->name . ' (' . $validated['period'] . ')',
                    'description' => $validated['notes'],
                    'status' => 'completed',
                    'transaction_date' => now(),
                    'user_id' => auth()->id(),
                ]);
            }

            // Create salary payment record
            $staffMember->salaryPayments()->create([
                'period' => $validated['period'],
                'base_salary' => $validated['base_salary'],
                'daily_rate' => $validated['daily_rate'],
                'unjustified_days' => $validated['unjustified_days'],
                'absence_deduction' => $absenceDeduction,
                'advances_deduction' => $validated['advances_deduction'],
                'overtime_amount' => $overtimeAmount,
                'bonus_amount' => $bonusAmount,
                'penalty_amount' => $penaltyAmount,
                'commission_amount' => $commissionAmount,
                'gross_salary' => $grossSalary,
                'net_amount' => $validated['net_amount'],
                'currency' => $validated['currency'],
                'notes' => $validated['notes'],
                'paid_by' => auth()->id(),
                'store_id' => $validated['store_id'],
                'financial_transaction_id' => $transaction?->id,
            ]);

            // Mark advances as deducted if there were any
            if ($validated['advances_deduction'] > 0) {
                $staffMember->salaryAdvances()
                    ->where('status', 'approved')
                    ->update(['status' => 'deducted']);
            }

            // Mark adjustments as processed (pending + approved)
            $staffMember->salaryAdjustments()
                ->where('period', $validated['period'])
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'paid']);

            // Mark commissions as paid (pending + approved)
            $staffMember->commissionCalculations()
                ->where('period', $validated['period'])
                ->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'paid']);
        });

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'payroll'])
            ->with('success', __('messages.staff.payment_recorded'));
    }

    public function showPayment(SalaryPayment $payment)
    {
        $payment->load(['staffMember', 'payer', 'store', 'financialTransaction']);

        return view('staff.payment-detail', compact('payment'));
    }

    public function bulkPayment(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'staff_member_ids' => 'required|array|min:1',
            'staff_member_ids.*' => 'exists:staff_members,id',
        ]);

        $currentMonth = now()->format('Y-m');

        $paidCount = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($validated, $currentMonth, &$paidCount, &$totalAmount) {
            $staffMembers = StaffMember::with(['currentSalary', 'salaryAdvances', 'leaves', 'salaryAdjustments', 'employeeCommissions'])
                ->whereIn('id', $validated['staff_member_ids'])
                ->whereHas('currentSalary')
                ->whereDoesntHave('salaryPayments', function ($q) use ($currentMonth) {
                    $q->where('period', $currentMonth);
                })
                ->get();

            foreach ($staffMembers as $staffMember) {
                $payrollData = $this->payrollService->calculatePayrollForUser($staffMember, $currentMonth);

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
                        'label' => 'Salary Payment - ' . $staffMember->name . ' (' . $currentMonth . ')',
                        'description' => 'Bulk payment',
                        'status' => 'completed',
                        'transaction_date' => now(),
                        'user_id' => auth()->id(),
                    ]);
                }

                // Create salary payment with all components
                $staffMember->salaryPayments()->create([
                    'period' => $currentMonth,
                    'base_salary' => $payrollData['base_salary'],
                    'daily_rate' => $payrollData['daily_rate'],
                    'unjustified_days' => $payrollData['unjustified_days'],
                    'absence_deduction' => $payrollData['absence_deduction'],
                    'advances_deduction' => $payrollData['advances_total'],
                    'overtime_amount' => $payrollData['overtime_amount'],
                    'bonus_amount' => $payrollData['bonus_amount'],
                    'penalty_amount' => $payrollData['penalty_amount'],
                    'commission_amount' => $payrollData['commission_amount'],
                    'gross_salary' => $payrollData['gross_salary'],
                    'net_amount' => $payrollData['net_amount'],
                    'currency' => $payrollData['currency'],
                    'notes' => 'Bulk payment',
                    'paid_by' => auth()->id(),
                    'store_id' => $validated['store_id'],
                    'financial_transaction_id' => $transaction?->id,
                ]);

                // Mark advances as deducted
                if ($payrollData['advances_total'] > 0) {
                    $staffMember->salaryAdvances()
                        ->where('status', 'approved')
                        ->update(['status' => 'deducted']);
                }

                // Mark adjustments as processed (pending + approved)
                $staffMember->salaryAdjustments()
                    ->where('period', $currentMonth)
                    ->whereIn('status', ['pending', 'approved'])
                    ->update(['status' => 'paid']);

                // Mark commissions as paid (pending + approved)
                $staffMember->commissionCalculations()
                    ->where('period', $currentMonth)
                    ->whereIn('status', ['pending', 'approved'])
                    ->update(['status' => 'paid']);

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
        $payment->load(['staffMember.store', 'payer', 'store']);

        // Load commission calculations for the period
        $commissionCalculations = $payment->staffMember->commissionCalculations()
            ->where('period', $payment->period)
            ->with('employeeCommission')
            ->get();

        $pdf = Pdf::loadView('staff.payslip', compact('payment', 'commissionCalculations'));

        $filename = 'payslip_' . $payment->staffMember->name . '_' . $payment->period . '.pdf';
        $filename = str_replace(' ', '_', $filename);

        return $pdf->download($filename);
    }

    public function toggleTransfer(Request $request, SalaryPayment $payment)
    {
        $validated = $request->validate([
            'transfer_reference' => 'nullable|string|max:255',
        ]);

        if ($payment->is_transferred) {
            $payment->update([
                'is_transferred' => false,
                'transferred_at' => null,
                'transfer_reference' => null,
            ]);
        } else {
            $payment->update([
                'is_transferred' => true,
                'transferred_at' => now(),
                'transfer_reference' => $validated['transfer_reference'] ?? null,
            ]);
        }

        return redirect()
            ->route('staff.show', ['staffMember' => $payment->staff_member_id, 'tab' => 'payroll'])
            ->with('success', __('messages.staff.transfer_updated'));
    }

    // ==================== QUOTAS ====================

    public function quotas(StaffMember $staffMember)
    {
        $quotaBalances = $this->leaveQuotaService->getQuotaBalances($staffMember);

        return view('staff.partials.tab-quotas', compact('staffMember', 'quotaBalances'));
    }

    public function storeQuota(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,sick,dayoff',
            'year' => 'required|integer|min:2020|max:2100',
            'annual_quota' => 'required|numeric|min:0|max:365',
            'monthly_accrual' => 'required|numeric|min:0|max:31',
            'carryover_days' => 'required|numeric|min:0|max:365',
        ]);

        $this->leaveQuotaService->updateQuota($staffMember, $validated['type'], $validated['year'], [
            'annual_quota' => $validated['annual_quota'],
            'monthly_accrual' => $validated['monthly_accrual'],
            'carryover_days' => $validated['carryover_days'],
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'quotas'])
            ->with('success', __('messages.staff.quota_updated'));
    }

    // ==================== COMMISSIONS ====================

    public function commissions(StaffMember $staffMember)
    {
        $staffMember->load(['employeeCommissions', 'commissionCalculations.employeeCommission']);
        $stores = Store::orderBy('name')->get();
        $resellers = Reseller::orderBy('name')->get();

        return view('staff.partials.tab-commissions', compact('staffMember', 'stores', 'resellers'));
    }

    public function storeCommission(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:store_sales,reseller_sales',
            'source_id' => 'nullable|integer',
            'percentage' => 'required|numeric|min:0.01|max:100',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        $staffMember->employeeCommissions()->create([
            'source_type' => $validated['source_type'],
            'source_id' => $validated['source_id'] ?: null,
            'percentage' => $validated['percentage'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'commissions'])
            ->with('success', __('messages.staff.commission_created'));
    }

    public function deleteCommission(EmployeeCommission $commission)
    {
        $staffMemberId = $commission->staff_member_id;
        $commission->delete();

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMemberId, 'tab' => 'commissions'])
            ->with('success', __('messages.staff.commission_deleted'));
    }

    public function toggleCommission(EmployeeCommission $commission)
    {
        $commission->update(['is_active' => !$commission->is_active]);

        return redirect()
            ->route('staff.show', ['staffMember' => $commission->staff_member_id, 'tab' => 'commissions'])
            ->with('success', __('messages.staff.commission_toggled'));
    }

    public function calculateCommissions(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $calculations = $this->commissionService->calculateMonthlyCommissions($staffMember, $validated['period']);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'commissions'])
            ->with('success', __('messages.staff.commissions_calculated', ['count' => count($calculations)]));
    }

    public function approveCommission(Request $request, CommissionCalculation $calculation)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        if ($validated['action'] === 'approve') {
            $calculation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $message = __('messages.staff.commission_approved');
        } else {
            $calculation->delete();
            $message = __('messages.staff.commission_rejected');
        }

        return redirect()
            ->route('staff.show', ['staffMember' => $calculation->staff_member_id, 'tab' => 'commissions'])
            ->with('success', $message);
    }

    // ==================== ADJUSTMENTS ====================

    public function adjustments(StaffMember $staffMember)
    {
        $staffMember->load(['salaryAdjustments.approver']);

        return view('staff.partials.tab-adjustments', compact('staffMember'));
    }

    public function storeAdjustment(Request $request, StaffMember $staffMember)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'type' => 'required|in:overtime,bonus,penalty,other',
            'amount' => 'required|numeric|min:0.01',
            'hours' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        // Auto-calculate amount for overtime if hours and rate provided
        if ($validated['type'] === 'overtime' && !empty($validated['hours']) && !empty($validated['hourly_rate'])) {
            $validated['amount'] = $validated['hours'] * $validated['hourly_rate'];
        }

        $staffMember->salaryAdjustments()->create([
            'period' => $validated['period'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'hours' => $validated['hours'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMember, 'tab' => 'adjustments'])
            ->with('success', __('messages.staff.adjustment_created'));
    }

    public function approveAdjustment(Request $request, SalaryAdjustment $adjustment)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $adjustment->update([
            'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $message = $validated['action'] === 'approve'
            ? __('messages.staff.adjustment_approved')
            : __('messages.staff.adjustment_rejected');

        return redirect()
            ->route('staff.show', ['staffMember' => $adjustment->staff_member_id, 'tab' => 'adjustments'])
            ->with('success', $message);
    }

    public function deleteAdjustment(SalaryAdjustment $adjustment)
    {
        $staffMemberId = $adjustment->staff_member_id;

        if ($adjustment->status !== 'pending') {
            return redirect()
                ->route('staff.show', ['staffMember' => $staffMemberId, 'tab' => 'adjustments'])
                ->with('error', __('messages.staff.cannot_delete_approved_adjustment'));
        }

        $adjustment->delete();

        return redirect()
            ->route('staff.show', ['staffMember' => $staffMemberId, 'tab' => 'adjustments'])
            ->with('success', __('messages.staff.adjustment_deleted'));
    }

    /**
     * Calculate individual performance metrics for a staff member
     */
    private function calculateStaffMemberPerformance(StaffMember $staffMember, string $period): array
    {
        $periodStart = \Carbon\Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Get shifts from linked user
        $shifts = $staffMember->user
            ? $staffMember->user->shifts()
                ->with(['store', 'sales'])
                ->whereBetween('started_at', [$periodStart, $periodEnd])
                ->orderBy('started_at', 'desc')
                ->get()
            : collect();

        // Calculate sales metrics (excluding voucher payments)
        $totalSales = $shifts->sum(fn($shift) => Sale::sumRealRevenue($shift->sales));
        $salesCount = $shifts->sum(fn($shift) => $shift->sales->count());

        // Count working days from schedule
        $workingDays = $staffMember->schedules->where('is_working_day', true)->count();
        $totalWorkingDaysInMonth = $workingDays * 4; // Approximate weeks

        // Count absences
        $absences = $staffMember->leaves()
            ->where('status', 'approved')
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('start_date', [$periodStart, $periodEnd])
                    ->orWhereBetween('end_date', [$periodStart, $periodEnd]);
            })
            ->get()
            ->sum(fn($leave) => $leave->getDaysCount());

        // Attendance rate
        $attendanceRate = $totalWorkingDaysInMonth > 0
            ? round((($totalWorkingDaysInMonth - $absences) / $totalWorkingDaysInMonth) * 100, 1)
            : 0;

        return [
            'shifts' => $shifts,
            'shifts_worked' => $shifts->count(),
            'total_sales' => $totalSales,
            'sales_count' => $salesCount,
            'working_days_scheduled' => $totalWorkingDaysInMonth,
            'absences' => $absences,
            'attendance_rate' => max(0, min(100, $attendanceRate)),
        ];
    }

    /**
     * Calculate individual planning data for a staff member
     */
    private function calculateStaffMemberPlanning(StaffMember $staffMember, string $month): array
    {
        $monthStart = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Get working days from schedule (day_of_week: 0=Sunday to 6=Saturday)
        $workingDays = $staffMember->schedules->where('is_working_day', true)->pluck('day_of_week')->toArray();

        // Get leaves for this month
        $leaves = $staffMember->leaves()
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                    ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                        $q2->where('start_date', '<=', $monthStart)
                            ->where('end_date', '>=', $monthEnd);
                    });
            })
            ->orderBy('start_date')
            ->get();

        // Build days array
        $days = [];
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalOff = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $monthStart->copy()->day($i);
            $dateStr = $date->format('Y-m-d');
            $dayOfWeek = $date->dayOfWeek;

            // Check if employee has leave on this day
            $leave = $leaves->first(function ($l) use ($dateStr) {
                return $l->start_date->format('Y-m-d') <= $dateStr && $l->end_date->format('Y-m-d') >= $dateStr;
            });

            if ($leave) {
                $days[$i] = [
                    'status' => 'absent',
                    'type' => $leave->type,
                    'leave_status' => $leave->status,
                    'reason' => $leave->reason,
                ];
                $totalAbsent++;
            } elseif (in_array($dayOfWeek, $workingDays)) {
                $days[$i] = [
                    'status' => 'present',
                    'type' => null,
                ];
                $totalPresent++;
            } else {
                $days[$i] = [
                    'status' => 'off',
                    'type' => null,
                ];
                $totalOff++;
            }
        }

        return [
            'days' => $days,
            'leaves' => $leaves,
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'total_off' => $totalOff,
        ];
    }
}
