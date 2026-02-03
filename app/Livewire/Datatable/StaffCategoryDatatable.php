<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Models\Role;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

class StaffCategoryDatatable extends Datatable
{
    public string $model = Role::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.staff-category.create';
    public string $newResourceLinkPermission = 'role.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:user-plus';
    public string $actionColumnIcon = 'lucide:settings';

    public function mount(): void
    {
        parent::mount();
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Staff Category');
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
                'id' => 'user_count',
                'title' => __('Users'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'user_count',
            ],
            [
                'id' => 'permissions_count',
                'title' => __('Permissions'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'permissions_count',
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
            ->withCount('users')
            ->withCount('permissions')
            ->whereNotIn('name', [Role::ADMIN, 'Customer'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            });

        return $this->sortQuery($query);
    }

    public function renderNameColumn(Role $role): string
    {
        return '<span class="font-medium">' . e($role->name) . '</span>';
    }

    public function renderUserCountColumn(Role $role): string
    {
        $count = $role->users_count ?? 0;
        return '<span class="text-sm">' . e((string) $count) . '</span>';
    }

    public function renderPermissionsCountColumn(Role $role): string
    {
        $count = $role->permissions_count ?? 0;
        return '<span class="text-sm">' . e((string) $count) . '</span>';
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
        $roles = Role::whereIn('id', $ids)
            ->whereNotIn('name', [Role::ADMIN, 'Customer'])
            ->get();

        $deletedCount = 0;
        foreach ($roles as $role) {
            // Skip protected roles in demo mode
            if (config('app.demo_mode') && in_array($role->name, ['User', 'Manager', 'Moderator', 'Manager with statistics'])) {
                continue;
            }

            $this->authorize('delete', $role);
            $role->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    public function handleRowDelete(Model|Role $role): bool
    {
        // Ensure it's a staff category
        if ($role->name === Role::ADMIN || $role->name === 'Customer') {
            throw new \Exception(__('This is not a staff category.'));
        }

        // Check if this is a protected role in demo mode
        if (config('app.demo_mode') && in_array($role->name, ['User', 'Manager', 'Moderator', 'Manager with statistics'])) {
            throw new \Exception(__('Cannot delete protected staff categories in demo mode.'));
        }

        $this->authorize('delete', $role);
        $role->delete();

        return true;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'role.view',
            'create' => 'role.create',
            'edit' => 'role.edit',
            'delete' => 'role.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.staff-category.create',
            'edit' => 'admin.staff-category.edit',
            'delete' => 'admin.staff-category.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['staff_category' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => $this->enableLivewireDelete ? '' : route('admin.staff-category.bulk-delete'),
            'method' => 'DELETE',
        ];
    }
}

