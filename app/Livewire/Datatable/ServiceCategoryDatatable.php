<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\ServiceCategory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceCategoryDatatable extends Datatable
{
    public string $model = ServiceCategory::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.service-category.create';
    public string $newResourceLinkPermission = 'service-category.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:plus';
    public string $actionColumnIcon = 'lucide:settings';

    public function mount(): void
    {
        parent::mount();
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Service');
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by name...');
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
                'id' => 'name_sv',
                'title' => __('Name (Swedish)'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'name_sv',
            ],
            [
                'id' => 'created_at',
                'title' => __('Created At'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'created_at',
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
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('name_sv', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function renderNameColumn(ServiceCategory $serviceCategory): string
    {
        return '<button type="button" 
            class="font-medium text-blue-600 hover:text-blue-800 hover:underline focus:outline-none open-service-types-modal"
            data-id="' . $serviceCategory->id . '"
            data-name="' . e($serviceCategory->name) . '"
            title="' . __('View Service Types') . '">
            ' . e($serviceCategory->name) . '
        </button>';
    }

    public function renderNameSvColumn(ServiceCategory $serviceCategory): string
    {
        $nameSv = $serviceCategory->name_sv ?? '-';
        return '<span class="text-sm">' . e($nameSv) . '</span>';
    }

    public function renderCreatedAtColumn($item): string
    {
        if (! array_key_exists('created_at', $item->getAttributes()) || ! $item->created_at) {
            return '';
        }

        $short = $item->created_at->format('d M Y');
        $full = $item->created_at->format('Y-m-d H:i:s');

        return '<span class="text-sm" title="' . e($full) . '">' . e($short) . '</span>';
    }

    protected function handleBulkDelete(array $ids): int
    {
        $serviceCategories = ServiceCategory::whereIn('id', $ids)->get();

        $deletedCount = 0;
        foreach ($serviceCategories as $serviceCategory) {
            $this->authorize('delete', $serviceCategory);
            $serviceCategory->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    public function handleRowDelete(Model|ServiceCategory $serviceCategory): bool
    {
        $this->authorize('delete', $serviceCategory);
        $serviceCategory->delete();

        return true;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'service-category.view',
            'create' => 'service-category.create',
            'edit' => 'service-category.edit',
            'delete' => 'service-category.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.service-category.create',
            'edit' => 'admin.service-category.edit',
            'delete' => 'admin.service-category.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['service_category' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => $this->enableLivewireDelete ? '' : route('admin.service-category.bulk-delete'),
            'method' => 'DELETE',
        ];
    }

    public function renderAfterActionEdit($item): string|Renderable
    {
        if (!($item instanceof ServiceCategory)) {
            return '';
        }

        if (!Auth::user()->can('status.view')) {
            return '';
        }

        $statusUrl = route('admin.status.index', $item->id);
        return '<x-buttons.action-item
            href="' . e($statusUrl) . '"
            icon="lucide:list-checks"
            label="' . __('Statuses') . '"
        />';
    }
}

