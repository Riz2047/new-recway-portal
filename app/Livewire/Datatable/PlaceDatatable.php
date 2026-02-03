<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Place;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

class PlaceDatatable extends Datatable
{
    public string $model = Place::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.place.create';
    public string $newResourceLinkPermission = 'place.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:map-pin';
    public string $actionColumnIcon = 'lucide:settings';

    public function mount(): void
    {
        parent::mount();
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Place');
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
                $query->where('name', 'like', "%{$this->search}%");
            });

        return $this->sortQuery($query);
    }

    public function renderNameColumn(Place $place): string
    {
        return '<span class="font-medium">' . e($place->name) . '</span>';
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
        $places = Place::whereIn('id', $ids)->get();

        $deletedCount = 0;
        foreach ($places as $place) {
            $this->authorize('delete', $place);
            $place->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    public function handleRowDelete(Model|Place $place): bool
    {
        $this->authorize('delete', $place);
        $place->delete();

        return true;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'place.view',
            'create' => 'place.create',
            'edit' => 'place.edit',
            'delete' => 'place.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.place.create',
            'edit' => 'admin.place.edit',
            'delete' => 'admin.place.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['place' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => $this->enableLivewireDelete ? '' : route('admin.place.bulk-delete'),
            'method' => 'DELETE',
        ];
    }
}

