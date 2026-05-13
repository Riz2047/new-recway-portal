<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AllowedEmail;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\StandardBillingDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    // Password policy: min 14 chars, upper, lower, digit, special
    private const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{14,}$/';

    public function edit(): View
    {
        $user       = Auth::user();
        $customerId = $this->getCustomerId($user->id);
        $customer   = Customer::find($customerId);

        // Can the user edit their own email & phone?
        // Company managers with report access can; regular customers can always edit their own.
        $canEditEmailPhone = true;

        // Billing details
        $billing = StandardBillingDetail::where('cus_id', $customerId)->first();

        // Email notification settings
        $allowedEmail     = AllowedEmail::where('cus_id', $customerId)->first();
        $allowedStatusIds = $allowedEmail ? ($allowedEmail->allowed_status_ids ?? []) : [];

        // Statuses grouped by service category for email settings
        $serviceCategories = ServiceCategory::with(['statuses' => function ($q) {
            $q->orderBy('status');
        }])->orderBy('name')->get();

        return view('customer.account.index', compact(
            'user',
            'customer',
            'canEditEmailPhone',
            'billing',
            'allowedStatusIds',
            'serviceCategories',
        ));
    }

    // ── Update profile (Tab 1) ────────────────────────────────────────────

    public function update(Request $request): RedirectResponse
    {
        $user       = Auth::user();
        $customerId = $this->getCustomerId($user->id);
        $customer   = Customer::find($customerId);

        $rules = [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'   => 'required|string|max:50',
            'company' => 'nullable|string|max:255',
            'org_no'  => 'nullable|string|max:100',
        ];

        // Password is optional — only validate if provided
        if ($request->filled('password')) {
            $rules['password'] = [
                'required',
                'string',
                'min:14',
                'confirmed',
                function ($_attribute, $value, $fail) {
                    if (! preg_match(self::PASSWORD_REGEX, $value)) {
                        $fail(__('Password must be at least 14 characters and contain uppercase, lowercase, digit, and special character.'));
                    }
                },
            ];
        }

        $request->validate($rules);

        // Update users table
        $user->name  = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update customers table
        if ($customer) {
            $customer->phone   = $request->phone;
            $customer->company = $request->company;
            $customer->org_no  = $request->org_no;
            $customer->save();
        }

        return redirect()->route('customer.account')
            ->with('success', __('Account updated successfully.'))
            ->withFragment('profile');
    }

    // ── Update billing details (Tab 2) ────────────────────────────────────

    public function updateBilling(Request $request): RedirectResponse
    {
        $customerId = $this->getCustomerId(Auth::id());

        StandardBillingDetail::updateOrCreate(
            ['cus_id' => $customerId],
            [
                'referenceperson' => $request->input('referenceperson'),
                'reference'       => $request->input('reference'),
                'comment'         => $request->input('comment'),
            ]
        );

        return redirect()->route('customer.account')
            ->with('success', __('Billing details updated.'))
            ->withFragment('billing');
    }

    // ── Update email settings (Tab 3) ─────────────────────────────────────

    public function updateEmailSettings(Request $request): RedirectResponse
    {
        $customerId = $this->getCustomerId(Auth::id());

        $allowed = array_map('intval', $request->input('allowed_status_ids', []));

        AllowedEmail::updateOrCreate(
            ['cus_id' => $customerId],
            ['allowed_status_ids' => $allowed]
        );

        return redirect()->route('customer.account')
            ->with('success', __('Email settings updated.'))
            ->withFragment('email-settings');
    }

    // ── AJAX: toggle one status on/off ────────────────────────────────────

    public function toggleEmailStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $customerId = $this->getCustomerId(Auth::id());
        $statusId   = (int) $request->input('status_id');
        $checked    = (int) $request->input('checked'); // 1 = enable, 2 = disable

        $record = AllowedEmail::firstOrCreate(
            ['cus_id' => $customerId],
            ['allowed_status_ids' => []]
        );

        $ids = $record->allowed_status_ids ?? [];

        if ($checked === 1 && ! in_array($statusId, $ids)) {
            $ids[] = $statusId;
        } elseif ($checked === 2) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $statusId));
        }

        $record->allowed_status_ids = $ids;
        $record->save();

        return response()->json(['success' => true]);
    }

    // ── Helper ────────────────────────────────────────────────────────────

    private function getCustomerId(int $userId): ?int
    {
        return Customer::where('user_id', $userId)->value('id');
    }
}
