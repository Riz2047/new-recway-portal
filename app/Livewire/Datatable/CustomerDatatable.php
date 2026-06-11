<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerDatatable extends Datatable
{
    public string $model = Customer::class;
    public string $sort = 'users.name';
    public string $direction = 'asc';
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.customers.create';
    public string $newResourceLinkPermission = 'customer.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:user-plus';
    public string $actionColumnIcon = 'lucide:settings';

    public array $queryString = [
        ...parent::QUERY_STRING_DEFAULTS,
        'sort' => ['except' => 'users.name'],
    ];

    public function mount(): void
    {
        parent::mount();
        $this->newResourceLinkLabel = __('New Customer');
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by name, email, company or phone...');
    }

    protected function getHeaders(): array
    {
        return [
            [
                'id' => 'name',
                'title' => __('Name'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'users.name',
            ],
            [
                'id' => 'email',
                'title' => __('Email'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'users.email',
            ],
            [
                'id' => 'phone',
                'title' => __('Phone'),
                'width' => null,
                'sortable' => false,
            ],
            [
                'id' => 'company',
                'title' => __('Company'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'customers.company',
            ],
            [
                'id' => 'org_no',
                'title' => __('Org. Number'),
                'width' => null,
                'sortable' => false,
            ],
            [
                'id' => 'parent',
                'title' => __('Parent Customer'),
                'width' => null,
                'sortable' => false,
            ],
            [
                'id' => 'actions',
                'title' => __('Actions'),
                'width' => null,
                'sortable' => false,
                'is_action' => true,
            ],
        ];
    }

    protected function buildQuery(): QueryBuilder
    {
        $sort = $this->sort ?: 'users.name';
        $direction = $this->direction ?: 'asc';

        return QueryBuilder::for(Customer::class)
            ->with(['parent.user'])
            ->join('users', 'customers.user_id', '=', 'users.id')
            ->select('customers.*', 'users.name as user_name', 'users.email as user_email')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('users.name', 'like', "%{$this->search}%")
                        ->orWhere('users.email', 'like', "%{$this->search}%")
                        ->orWhere('customers.company', 'like', "%{$this->search}%")
                        ->orWhere('customers.phone', 'like', "%{$this->search}%")
                        ->orWhere('customers.org_no', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($sort, $direction);
    }

    public function renderNameColumn(Customer $customer): string
    {
        $name = e($customer->user_name ?? '—');
        $editUrl = e(route('admin.customers.edit', $customer->id));

        return '<a href="' . $editUrl . '" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">' . $name . '</a>';
    }

    public function renderEmailColumn(Customer $customer): string
    {
        $email = $customer->user_email ?? '';

        if (empty($email)) {
            return '<span class="text-sm text-gray-400">—</span>';
        }

        return '<a href="mailto:' . e($email) . '" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">' . e($email) . '</a>';
    }

    public function renderPhoneColumn(Customer $customer): string
    {
        return '<span class="text-sm">' . e($customer->phone ?? '—') . '</span>';
    }

    public function renderCompanyColumn(Customer $customer): string
    {
        return '<span class="text-sm font-medium">' . e($customer->company ?? '—') . '</span>';
    }

    public function renderOrgNoColumn(Customer $customer): string
    {
        return '<span class="text-sm text-gray-500 dark:text-gray-400">' . e($customer->org_no ?? '—') . '</span>';
    }

    public function renderParentColumn(Customer $customer): string
    {
        $parentName = $customer->parent?->user?->name ?? '—';

        return '<span class="text-sm text-gray-500 dark:text-gray-400">' . e($parentName) . '</span>';
    }

    protected function handleBulkDelete(array $ids): int
    {
        $customers = Customer::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($customers as $customer) {
            $this->authorize('delete', $customer);
            $this->deleteCustomerRelatedData($customer);
            $deletedCount++;
        }

        return $deletedCount;
    }

    public function handleRowDelete(Model|Customer $customer): bool
    {
        $this->authorize('delete', $customer);
        $this->deleteCustomerRelatedData($customer);

        return true;
    }

    private function deleteCustomerRelatedData(Customer $customer): void
    {
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

        $customer->delete();
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'customer.view',
            'create' => 'customer.create',
            'edit' => 'customer.edit',
            'delete' => 'customer.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.customers.create',
            'edit' => 'admin.customers.edit',
            'delete' => 'admin.customers.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['customer' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => '',
            'method' => 'DELETE',
        ];
    }
}
