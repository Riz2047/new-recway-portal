<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Status;
use Spatie\QueryBuilder\QueryBuilder;

class StatusDatatable extends Datatable
{
    public int $serviceCategoryId;

    public string $model = Status::class;

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by status, variable...');
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'status.view',
            'create' => 'status.create',
            'edit' => 'status.edit',
            'delete' => 'status.delete',
        ];
    }

    protected function getHeaders(): array
    {
        return [
            [
                'id' => 'status',
                'title' => __('Status'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'status',
            ],
            [
                'id' => 'status_sv',
                'title' => __('Status (Swedish)'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'status_sv',
            ],
            [
                'id' => 'variable',
                'title' => __('Variable'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'variable',
            ],
            [
                'id' => 'color',
                'title' => __('Color'),
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
            ->where('status_type', $this->serviceCategoryId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'like', "%{$this->search}%")
                        ->orWhere('status_sv', 'like', "%{$this->search}%")
                        ->orWhere('variable', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.status.create',
            'edit' => 'admin.status.edit',
            'delete' => 'admin.status.destroy',
        ];
    }

    protected function getRouteParameters(): array
    {
        return [
            'serviceCategory' => $this->serviceCategoryId,
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return [
            'serviceCategory' => $this->serviceCategoryId,
            'status' => $item->id,
        ];
    }

    public function renderVariableColumn(Status $status): string
    {
        return "<code class='text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded'>" . e($status->variable) . '</code>';
    }

    public function renderColorColumn(Status $status): string
    {
        if (! $status->color) {
            return "<span class='text-gray-400'>-</span>";
        }

        return "<div class='w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600' style='background-color: " . e($status->color) . "'></div>";
    }
}
