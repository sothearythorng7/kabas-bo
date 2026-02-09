<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\StaffMember;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'store', 'staffMember')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $stores = Store::all();
        $unlinkedStaffMembers = StaffMember::whereNull('user_id')
            ->where('contract_status', 'active')
            ->orderBy('name')
            ->get();

        return view('users.create', compact('roles', 'stores', 'unlinkedStaffMembers'));
    }

    public function store(Request $request, LeaveQuotaService $leaveQuotaService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'role' => 'required|string|exists:roles,name',
            'store_id' => 'required|exists:stores,id',
            'pin_code' => 'nullable|digits:6',
            'staff_mode' => 'nullable|in:none,create,link',
            'staff_phone' => 'nullable|string|max:50',
            'staff_hire_date' => 'nullable|date',
            'staff_base_salary' => 'nullable|numeric|min:0',
            'staff_currency' => 'nullable|string|size:3',
            'staff_member_id' => 'nullable|exists:staff_members,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'store_id'=> $request->store_id,
            'pin_code' => $request->pin_code,
            'locale' => $request->locale ?? 'fr',
        ]);

        $user->assignRole($request->role);

        $staffMode = $request->input('staff_mode', 'none');

        // Create new staff member
        if ($staffMode === 'create') {
            $staffMember = StaffMember::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->staff_phone,
                'hire_date' => $request->staff_hire_date,
                'store_id' => $request->store_id,
                'contract_status' => 'active',
                'user_id' => $user->id,
            ]);

            // Create initial salary if provided
            if ($request->filled('staff_base_salary') && $request->staff_base_salary > 0) {
                $staffMember->salaries()->create([
                    'base_salary' => $request->staff_base_salary,
                    'currency' => $request->staff_currency ?? 'USD',
                    'effective_from' => $request->staff_hire_date ?? now(),
                    'created_by' => auth()->id(),
                ]);
            }

            // Initialize leave quotas
            $leaveQuotaService->initializeQuotasForEmployee($staffMember);
        }
        // Link to existing staff member
        elseif ($staffMode === 'link' && $request->filled('staff_member_id')) {
            StaffMember::where('id', $request->staff_member_id)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', __('messages.user.created'));
    }

    public function edit(User $user)
    {
        $user->load('staffMember');
        $roles = Role::all();
        $stores = Store::all();
        $unlinkedStaffMembers = StaffMember::whereNull('user_id')
            ->where('contract_status', 'active')
            ->orderBy('name')
            ->get();

        return view('users.edit', compact('user', 'roles', 'stores', 'unlinkedStaffMembers'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|confirmed',
            'role' => 'required|string|exists:roles,name',
            'store_id' => 'required|exists:stores,id',
            'pin_code' => 'nullable|digits:6',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'store_id'=> $request->store_id,
            'pin_code' => $request->pin_code,
            'locale' => $request->locale,
        ]);

        $user->syncRoles([$request->role]);

        // Handle staff member linking/unlinking
        if ($request->filled('staff_member_id')) {
            // Unlink previous staff member if any
            StaffMember::where('user_id', $user->id)->update(['user_id' => null]);
            // Link new staff member
            StaffMember::where('id', $request->staff_member_id)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        } elseif ($request->has('staff_member_id') && $request->staff_member_id === null) {
            // Explicitly unlink
            StaffMember::where('user_id', $user->id)->update(['user_id' => null]);
        }

        return redirect()->route('users.index')->with('success', __('messages.user.updated'));
    }

    public function destroy(User $user)
    {
        // Unlink staff member before deleting user
        StaffMember::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();
        return redirect()->route('users.index')->with('success', __('messages.user.deleted'));
    }
}
