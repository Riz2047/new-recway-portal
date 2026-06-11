<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\Status;
use App\Services\CustomerService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    /**
     * Display a listing of customers.
     */
    public function index(): Renderable
    {
        $this->authorize('viewAny', Customer::class);

        $this->setBreadcrumbTitle(__('Customers'));

        return $this->renderViewWithBreadcrumbs('backend.pages.customers.index');
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): Renderable
    {
        $this->authorize('create', Customer::class);

        $this->setBreadcrumbTitle(__('New Customer'))
            ->addBreadcrumbItem(__('Customers'), route('admin.customers.index'));

        // Get all services/service_types
        $services = collect([]);
        if (Schema::hasTable('service_types')) {
            $services = ServiceType::orderBy('name')->get();
        }

        // Get service categories
        $serviceCategories = ServiceCategory::orderBy('name')->get();

        // Get statuses grouped by service category
        $statusesByCategory = [];
        foreach ($serviceCategories as $category) {
            $statusesByCategory[$category->id] = Status::where('status_type', $category->id)
                ->orderBy('status')
                ->get();
        }

        // Get all statuses (for combine_status dropdown)
        $allStatuses = Status::where('status_type', 3) // Status type 3 for combine statuses
            ->orderBy('status')
            ->get();

        // Get parent customers
        $parentCustomers = Customer::with('user')
            // ->whereNull('parent_id')
            ->get()
            ->sortBy(fn ($c) => $c->user->name);
        // Get permissions (user_permissions table)
        $permissions = collect([]);
        if (Schema::hasTable('user_permissions')) {
            $permissions = DB::table('user_permissions')
                ->where('user_type', '!=', 3) // Exclude user_type 3
                ->orderBy('title')
                ->get();
        }

        // Get default registration email from settings
        $defaultRegEmail = null;
        if (Schema::hasTable('settings')) {
            $setting = DB::table('settings')
                ->where('option_name', 'cus_reg_msg')
                ->first();
            $defaultRegEmail = $setting->option_value ?? null;
        }

        // Get departments (if table exists)
        $departments = collect([]);
        if (Schema::hasTable('departments')) {
            $departments = DB::table('departments')->orderBy('dep_name')->get();
        }

        return $this->renderViewWithBreadcrumbs('backend.pages.customers.create', [
            'services' => $services,
            'serviceCategories' => $serviceCategories,
            'statusesByCategory' => $statusesByCategory,
            'allStatuses' => $allStatuses,
            'parentCustomers' => $parentCustomers,
            'permissions' => $permissions,
            'defaultRegEmail' => $defaultRegEmail,
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:7',
            'phone' => 'nullable|string|max:255',
            'company' => 'required|string|max:255',
            'org_no' => 'required|string|max:255',
            'client_wish' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:customers,id',
            'cus_department' => 'nullable|integer',
            'interview_template' => 'nullable|boolean',
                        'interview_upload_allowed' => 'nullable|boolean',
            'send_security_report' => 'nullable|boolean',
            'send_email' => 'nullable|boolean',
            'combine_bk_and_security' => 'nullable|array',
            'combine_bk_and_security.*' => 'exists:service_types,id',
            'timra_report' => 'nullable|boolean',
                        'ellevio_report' => 'nullable|boolean',
                        'send_email_question' => 'nullable|boolean',
            'combine_status' => 'nullable|array',
            'combine_status.*' => 'exists:statuses,id',
            'combine_interview_service' => 'nullable|exists:service_types,id',
            'invoice_period' => 'nullable|in:day,week,month',
            'last_invoice_sent' => 'nullable|date',
            'changed_registration_email' => 'nullable|string',
            'company_manager' => 'nullable|boolean',
            'pref' => 'nullable|string|max:255',
            'ref' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'statuses' => 'nullable|array',
            'statuses.*' => 'exists:statuses,id',
            'services' => 'nullable|array',
            'services.*' => 'exists:service_types,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer',
        ]);

        try {
            $customer = $this->customerService->createCustomer($validated);

            session()->flash('success', __('Customer has been created successfully.'));

            return redirect()->route('admin.customers.index');
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to create customer: :message', ['message' => $e->getMessage()]));

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing a customer.
     */
    public function edit(int $id): Renderable
    {
        $customer = Customer::findOrFail($id);

        $this->authorize('update', $customer);

        $this->setBreadcrumbTitle(__('Edit Customer'))
            ->addBreadcrumbItem(__('Customers'), route('admin.customers.index'));

        // Get all services/interviews
        $services = collect([]);
        if (Schema::hasTable('service_types')) {
            $services = ServiceType::orderBy('name')->get();
        }

        // Get service categories
        $serviceCategories = ServiceCategory::orderBy('name')->get();

        // Get statuses grouped by service category
        $statusesByCategory = [];
        foreach ($serviceCategories as $category) {
            $statusesByCategory[$category->id] = Status::where('status_type', $category->id)
                ->orderBy('status')
                ->get();
        }

        // Get all statuses (for combine_status dropdown)
        $allStatuses = Status::where('status_type', 3)
            ->orderBy('status')
            ->get();

        // Get parent customers (exclude current customer)
        $parentCustomers = Customer::with('user')
            ->whereNull('parent_id')
            ->where('id', '!=', $customer->id)
            ->get()
            ->sortBy(fn ($c) => $c->user->name);

        // Get permissions
        $permissions = collect([]);
        if (Schema::hasTable('user_permissions')) {
            $permissions = DB::table('user_permissions')
                ->where('user_type', '!=', 3)
                ->orderBy('title')
                ->get();
        }

        // Get customer's selected services
        $customerServices = $customer->serviceTypes()->pluck('service_types.id')->toArray();

        // Get customer's selected permissions
        $customerPermissions = [];
        if (Schema::hasTable('user_allowed_permissions')) {
            $customerPermissions = DB::table('user_allowed_permissions')
                ->where('user_id', $customer->id)
                ->where('user_type', 2) // 2 = customer
                ->pluck('per_id')
                ->toArray();
        }

        // Get customer's selected statuses
        $customerStatuses = [];
        if (! empty($customer->statuses)) {
            $customerStatuses = explode(',', $customer->statuses);
        }

        // Get departments
        $departments = collect([]);
        if (Schema::hasTable('departments')) {
            $departments = DB::table('departments')->orderBy('dep_name')->get();
        }

        // Get standard billing details
        $billingDetails = null;
        if (Schema::hasTable('standard_billing_details')) {
            $billingDetails = DB::table('standard_billing_details')
                ->where('cus_id', $customer->id)
                ->first();
        }

        // Get company manager
        $companyManager = null;
        if (Schema::hasTable('company_manager')) {
            $companyManager = DB::table('company_manager')
                ->where('cus_id', $customer->id)
                ->first();
        }

        return $this->renderViewWithBreadcrumbs('backend.pages.customers.edit', [
            'customer' => $customer,
            'services' => $services,
            'serviceCategories' => $serviceCategories,
            'statusesByCategory' => $statusesByCategory,
            'allStatuses' => $allStatuses,
            'parentCustomers' => $parentCustomers,
            'permissions' => $permissions,
            'customerServices' => $customerServices,
            'customerPermissions' => $customerPermissions,
            'customerStatuses' => $customerStatuses,
            'departments' => $departments,
            'billingDetails' => $billingDetails,
            'companyManager' => $companyManager,
        ]);
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $customer->user_id,
            'password' => 'nullable|string|min:7',
            'phone' => 'nullable|string|max:255',
            'company' => 'required|string|max:255',
            'org_no' => 'required|string|max:255',
            'client_wish' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:customers,id',
            'cus_department' => 'nullable|integer',
            'interview_template' => 'nullable|boolean',
                        'interview_upload_allowed' => 'nullable|boolean',
            'send_security_report' => 'nullable|boolean',
            'send_email' => 'nullable|boolean',
            'combine_bk_and_security' => 'nullable|array',
            'combine_bk_and_security.*' => 'exists:service_types,id',
            'combine_interview_service' => 'nullable|exists:service_types,id',
            'timra_report' => 'nullable|boolean',
            'ellevio_report' => 'nullable|boolean',
            'send_email_question' => 'nullable|boolean',
            'combine_status' => 'nullable|array',
            'combine_status.*' => 'exists:statuses,id',
            'invoice_period' => 'nullable|in:day,week,month',
            'last_invoice_sent' => 'nullable|date',
            'changed_registration_email' => 'nullable|string',
            'company_manager' => 'nullable|boolean',
            'pref' => 'nullable|string|max:255',
            'ref' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'statuses' => 'nullable|array',
            'statuses.*' => 'exists:statuses,id',
            'services' => 'nullable|array',
            'services.*' => 'exists:service_types,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer',
            'old_email' => 'nullable|string',
        ]);

        // Add old email for email update tracking
        $validated['old_email'] = $customer->user->email;

        try {
            $customer = $this->customerService->updateCustomer($customer, $validated);

            session()->flash('success', __('Customer has been updated successfully.'));

            return redirect()->route('admin.customers.index');
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to update customer: :message', ['message' => $e->getMessage()]));

            return back()->withInput();
        }
    }

    /**
     * Remove a customer.
     */
    public function destroy(int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $this->authorize('delete', $customer);

        try {
            // Delete related records first
            if (Schema::hasTable('service_type_user')) {
                DB::table('service_type_user')->where('cus_id', $customer->id)->delete();
            }
            if (Schema::hasTable('user_allowed_permissions')) {
                DB::table('user_allowed_permissions')
                    ->where('user_id', $customer->id)
                    ->where('user_type', 2)
                    ->delete();
            }
            if (Schema::hasTable('allowed_emails')) {
                DB::table('allowed_emails')->where('cus_id', $customer->id)->delete();
            }
            if (Schema::hasTable('standard_billing_details')) {
                DB::table('standard_billing_details')->where('cus_id', $customer->id)->delete();
            }
            if (Schema::hasTable('company_manager')) {
                DB::table('company_manager')->where('cus_id', $customer->id)->delete();
            }

            // Delete customer
            $customer->delete();

            session()->flash('success', __('Customer has been deleted successfully.'));

            return redirect()->route('admin.customers.index');
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to delete customer: :message', ['message' => $e->getMessage()]));

            return back();
        }
    }

    /**
     * Get departments for a parent customer (AJAX)
     */
    public function getDepartments(Request $request)
    {
        $parentId = $request->input('parent_id');

        if (empty($parentId)) {
            return response()->json(['departments' => []]);
        }

        $departments = collect([]);
        if (Schema::hasTable('departments')) {
            $departments = DB::table('departments')
                ->where('cus_id', $parentId)
                ->orderBy('dep_name')
                ->get();
        }

        return response()->json(['departments' => $departments]);
    }

    /**
     * Get parent customer data (AJAX)
     */
    public function getParentCustomerData(Request $request)
    {
        $parentId = $request->input('parent_id');

        if (empty($parentId)) {
            return response()->json(['success' => false]);
        }

        $parent = Customer::find($parentId);

        if (! $parent) {
            return response()->json(['success' => false]);
        }

        // Get parent's services
        $parentServices = [];
        if (Schema::hasTable('service_type_user')) {
            $parentServices = DB::table('service_type_user')
                ->where('cus_id', $parent->id)
                ->pluck('service_type_id')
                ->toArray();
        }

        // Get parent's permissions
        $parentPermissions = [];
        if (Schema::hasTable('user_allowed_permissions')) {
            $parentPermissions = DB::table('user_allowed_permissions')
                ->where('user_id', $parentId)
                ->where('user_type', 2)
                ->pluck('per_id')
                ->toArray();
        }

        // Get parent's statuses
        $parentStatuses = [];
        if (! empty($parent->statuses)) {
            $parentStatuses = explode(',', $parent->statuses);
        }

        // Get departments
        $departments = collect([]);
        if (Schema::hasTable('departments')) {
            $departments = DB::table('departments')
                ->where('cus_id', $parentId)
                ->orderBy('dep_name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'invoice_period' => $parent->invoice_period,
                'interview_upload_allowed' => $parent->interview_upload_allowed,
                'statuses' => $parent->statuses,
                'combine_bk_and_security' => $parent->combine_bk_and_security,
                'combine_interview_service' => $parent->combine_interview_service,
                'combine_status' => $parent->combine_status,
                'sent_email' => $parent->sent_email,
                'timra_report' => $parent->timra_report,
                'ellevio_report' => $parent->ellevio_report,
                'send_email_question' => $parent->send_email_question,
            ],
            'services' => $parentServices,
            'permissions' => $parentPermissions,
            'statuses' => $parentStatuses,
            'departments' => $departments,
        ]);
    }

    // public function getTabData(Request $request, int $id): JsonResponse
    // {
    //     $customer = Customer::with('user')->findOrFail($id);
    //     $this->authorize('update', $customer);

    //     $tab = (string) $request->query('tab', '');
    //     if (! in_array($tab, ['status_manager', 'billing', 'messages'], true)) {
    //         return response()->json(['success' => false, 'message' => __('Invalid tab requested.')], 422);
    //     }

    //     if ($tab === 'billing') {
    //         $billingDetails = null;
    //         if (Schema::hasTable('standard_billing_details')) {
    //             $billingDetails = DB::table('standard_billing_details')
    //                 ->where('cus_id', $customer->id)
    //                 ->first();
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'html' => view('backend.pages.customers.partials.billing', [
    //                 'billingDetails' => $billingDetails,
    //             ])->render(),
    //         ]);
    //     }

    //     if ($tab === 'status_manager') {
    //         $statusIds = ! empty($customer->statuses)
    //             ? array_values(array_filter(array_map('intval', explode(',', (string) $customer->statuses))))
    //             : [];

    //         $statuses = empty($statusIds)
    //             ? collect([])
    //             : Status::query()
    //                 ->whereIn('id', $statusIds)
    //                 ->orderBy('status')
    //                 ->get(['id', 'status', 'status_sv', 'status_type', 'variable']);

    //         $allowedEmailStatusIds = $customer->allowed_email_status_ids ?? [];

    //         return response()->json([
    //             'success' => true,
    //             'html' => view('backend.pages.customers.partials.status-manager', [
    //                 'statuses' => $statuses,
    //                 'allowedEmailStatusIds' => $allowedEmailStatusIds,
    //             ])->render(),
    //         ]);
    //     }

    //     $messageServiceColumn = Schema::hasColumn('messages', 'servicetype_id') ? 'servicetype_id' : 'interview_id';
    //     $messages = collect([]);

    //     if (Schema::hasTable('messages')) {
    //         $messages = DB::table('messages as m')
    //             ->leftJoin('service_types as st', "m.{$messageServiceColumn}", '=', 'st.id')
    //             ->where('m.cus_id', $customer->id)
    //             ->select([
    //                 'm.id',
    //                 "m.{$messageServiceColumn} as service_type_id",
    //                 'st.name as service_name',
    //                 'm.cus_msg',
    //                 'm.can_msg',
    //                 'm.admin_msg',
    //                 'm.pending_msg',
    //             ])
    //             ->orderBy('st.name')
    //             ->limit(200)
    //             ->get();
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'html' => view('backend.pages.customers.partials.messages', [
    //             'messages' => $messages,
    //         ])->render(),
    //     ]);
    // }
}
