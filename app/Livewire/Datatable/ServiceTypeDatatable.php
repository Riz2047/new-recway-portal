<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\ServiceType;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceTypeDatatable extends Datatable
{
    public string $model = ServiceType::class;
    public array $disabledRoutes = ['view', 'create', 'edit', 'delete'];
    public string $newResourceLinkRouteName = '';
    public string $newResourceLinkPermission = '';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:plus';
    public string $actionColumnIcon = 'lucide:settings';
    public int|string $perPage = 10;
    public bool $enableCheckbox = false;
    
    public ?int $categoryId = null;

    public function mount(): void
    {
        parent::mount();
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Service Type');
    }

    #[\Livewire\Attributes\On('refreshServiceTypeDatatable')]
    public function refreshDatatable($categoryId = null): void
    {
        if ($categoryId) {
            $this->categoryId = (int) $categoryId;
        }
        $this->resetPage();
    }

    public function setCategoryId($categoryId): void
    {
        $this->categoryId = (int) $categoryId;
        $this->resetPage();
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by name or description...');
    }

    protected function getHeaders(): array
    {
        return [
            [
                'id' => 'name',
                'title' => __('Name'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'name',
            ],
            [
                'id' => 'price',
                'title' => __('Price'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'price',
            ],
            [
                'id' => 'description',
                'title' => __('Description'),
                'width' => null,
                'sortable' => false,
            ],
            [
                'id' => 'customers',
                'title' => __('Customers'),
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
        $query = QueryBuilder::for($this->model)
            ->with('customers')
            ->when($this->categoryId, function ($q) {
                $q->where('service_category_id', $this->categoryId);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function renderNameColumn(ServiceType $serviceType): string
    {
        return '<div class="flex flex-col gap-1">
            <span class="font-medium text-gray-900 dark:text-white">' . e($serviceType->name) . '</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">' . e($serviceType->description ?? '-') . '</span>
        </div>';
    }

    public function renderPriceColumn(ServiceType $serviceType): string
    {
        $price = $serviceType->price ? (float) $serviceType->price : 0;
        $formatted = number_format($price, 2);
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
            $' . e($formatted) . '
        </span>';
    }

    public function renderDescriptionColumn(ServiceType $serviceType): string
    {
        return '<span class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">' . e($serviceType->description ?? '-') . '</span>';
    }

    public function renderCustomersColumn(ServiceType $serviceType): Renderable
    {
        return view('backend.pages.service-type.partials.customers-badge', compact('serviceType'));
    }

    public function renderActionsColumn($item): string|Renderable
    {
        $serviceType = $item;
        return view('backend.pages.service-type.partials.actions-buttons', compact('serviceType'));
    }

    protected function handleBulkDelete(array $ids): int
    {
        return 0;
    }

    public function handleRowDelete(Model|ServiceType $serviceType): bool
    {
        return false;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'service-type.view',
            'create' => 'service-type.create',
            'edit' => 'service-type.edit',
            'delete' => 'service-type.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [];
    }
}
