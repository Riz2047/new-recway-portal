<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CompanyManager;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyUserController extends Controller
{
    // ── Access guard ─────────────────────────────────────────────────────────
    private function getManagerOrAbort(): CompanyManager
    {
        $customerId = Customer::where('user_id', Auth::id())->value('id');

        $manager = CompanyManager::where('cus_id', $customerId)
            ->where('can_view_report', 1)
            ->first();

        if (! $manager || ! trim($manager->company ?? '')) {
            abort(403, __('Access denied. Company manager privilege required.'));
        }

        return $manager;
    }

    // ── List company staff ───────────────────────────────────────────────────

    public function index(): View|RedirectResponse
    {
        $manager    = $this->getManagerOrAbort();
        $authUserId = Auth::id();
        $company    = trim($manager->company);

        // All customers in the same company (via customers table)
        $staffCustomers = Customer::whereRaw('TRIM(company) = ?', [$company])
            ->orderBy('id')
            ->get(['id', 'user_id', 'phone', 'company']);

        // Load associated user data
        $staff = $staffCustomers->map(function ($customer) use ($authUserId) {
            $user = User::find($customer->user_id);
            if (! $user) return null;

            $loginStatus = DB::table('customer_login_statuses')
                ->where('customer_id', $customer->id)
                ->first();

            return (object) [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'phone'       => $customer->phone,
                'company'     => $customer->company,
                'is_active'   => $loginStatus ? (bool) $loginStatus->is_active : true,
                'is_self'     => $user->id === $authUserId,
            ];
        })->filter()->values();

        return view('customer.company-users.index', compact('staff', 'manager'));
    }

    // ── Toggle active status ─────────────────────────────────────────────────

    public function toggleStatus(Request $request): JsonResponse
    {
        $manager = $this->getManagerOrAbort();

        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'is_active'   => 'required|boolean',
        ]);

        $customerId = (int) $request->customer_id;
        $newActive  = (bool) $request->is_active;

        // Prevent manager from toggling their own account
        $authCustomerId = Customer::where('user_id', Auth::id())->value('id');
        if ($customerId === (int) $authCustomerId) {
            return response()->json(['success' => false, 'error' => __('You cannot deactivate your own account.')], 422);
        }

        // Verify same company
        $target = Customer::findOrFail($customerId);
        if (trim($target->company) !== trim($manager->company)) {
            return response()->json(['success' => false, 'error' => __('Unauthorized.')], 403);
        }

        // Read old value
        $existing  = DB::table('customer_login_statuses')->where('customer_id', $customerId)->first();
        $oldActive = $existing ? (bool) $existing->is_active : true;

        // Update or insert
        DB::table('customer_login_statuses')->updateOrInsert(
            ['customer_id' => $customerId],
            ['is_active' => $newActive, 'updated_at' => now(), 'created_at' => now()]
        );

        // Audit log only on actual change
        if ($oldActive !== $newActive) {
            DB::table('company_manager_customer_audit_logs')->insert([
                'manager_customer_id' => (int) $authCustomerId,
                'target_customer_id'  => $customerId,
                'action'              => 'is_active',
                'old_value'           => $oldActive ? '1' : '0',
                'new_value'           => $newActive ? '1' : '0',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        return response()->json(['success' => true, 'is_active' => $newActive]);
    }

    // ── Update email + phone ─────────────────────────────────────────────────

    public function updateStaff(Request $request): JsonResponse
    {
        $manager = $this->getManagerOrAbort();

        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'email'       => 'required|email|max:255',
            'phone'       => 'nullable|string|max:255',
        ]);

        $customerId = (int) $request->customer_id;

        // Verify same company
        $targetCustomer = Customer::findOrFail($customerId);
        if (trim($targetCustomer->company) !== trim($manager->company)) {
            return response()->json(['success' => false, 'error' => __('Unauthorized.')], 403);
        }

        // Load user to check email uniqueness
        $targetUser = User::findOrFail($targetCustomer->user_id);

        // Email unique check (exclude current user)
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $targetUser->id,
        ]);

        $authCustomerId = Customer::where('user_id', Auth::id())->value('id');
        $oldEmail = $targetUser->email;
        $oldPhone = $targetCustomer->phone;
        $newEmail = $request->email;
        $newPhone = $request->phone;

        // Update user email
        if ($oldEmail !== $newEmail) {
            $targetUser->email = $newEmail;
            $targetUser->save();

            DB::table('company_manager_customer_audit_logs')->insert([
                'manager_customer_id' => (int) $authCustomerId,
                'target_customer_id'  => $customerId,
                'action'              => 'email',
                'old_value'           => $oldEmail,
                'new_value'           => $newEmail,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // Update customer phone
        if ($oldPhone !== $newPhone) {
            $targetCustomer->phone = $newPhone;
            $targetCustomer->save();

            DB::table('company_manager_customer_audit_logs')->insert([
                'manager_customer_id' => (int) $authCustomerId,
                'target_customer_id'  => $customerId,
                'action'              => 'phone',
                'old_value'           => $oldPhone,
                'new_value'           => $newPhone,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'email'   => $newEmail,
            'phone'   => $newPhone,
        ]);
    }
}
