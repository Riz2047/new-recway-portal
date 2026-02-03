<?php

declare(strict_types=1);

namespace App\Livewire\Datatable;

use App\Enums\Hooks\UserActionHook;
use App\Enums\Hooks\UserFilterHook;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class StaffDatatable extends Datatable
{
    public bool $showEmail = true;
    public bool $showPhone = true;
    public bool $showOrders = true;
    public array $queryString = [
        ...parent::QUERY_STRING_DEFAULTS,
        'showEmail' => ['except' => true],
        'showPhone' => ['except' => true],
        'showOrders' => ['except' => true],
    ];
    public string $model = User::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.staff.create';
    public string $newResourceLinkPermission = 'user.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:user-plus';
    public string $actionColumnIcon = 'lucide:settings';

    public function mount(): void
    {
        parent::mount();
        $this->showEmail = session('staff_datatable_show_email', true);
        $this->showPhone = session('staff_datatable_show_phone', true);
        $this->showOrders = session('staff_datatable_show_orders', true);
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Staff');
        $this->dispatch('staff-columns-updated', showEmail: $this->showEmail, showPhone: $this->showPhone, showOrders: $this->showOrders);
    }

    public function updatedShowEmail($value): void
    {
        session(['staff_datatable_show_email' => $value]);
        $this->resetPage();
        $this->dispatch('staff-columns-updated', showEmail: $this->showEmail, showPhone: $this->showPhone, showOrders: $this->showOrders);
    }

    public function updatedShowPhone($value): void
    {
        session(['staff_datatable_show_phone' => $value]);
        $this->resetPage();
        $this->dispatch('staff-columns-updated', showEmail: $this->showEmail, showPhone: $this->showPhone, showOrders: $this->showOrders);
    }

    public function updatedShowOrders($value): void
    {
        session(['staff_datatable_show_orders' => $value]);
        $this->resetPage();
        $this->dispatch('staff-columns-updated', showEmail: $this->showEmail, showPhone: $this->showPhone, showOrders: $this->showOrders);
    }

    public function toggleEmailColumn(): void
    {
        $this->showEmail = !$this->showEmail;
        $this->updatedShowEmail($this->showEmail);
    }

    public function togglePhoneColumn(): void
    {
        $this->showPhone = !$this->showPhone;
        $this->updatedShowPhone($this->showPhone);
    }

    public function toggleOrdersColumn(): void
    {
        $this->showOrders = !$this->showOrders;
        $this->updatedShowOrders($this->showOrders);
    }

    public function getSearchbarPlaceholder(): string
    {
        return __('Search by name or email...');
    }

    protected function getHeaders(): array
    {
        $headers = [
            [
                'id' => 'name',
                'title' => __('Name'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'first_name',
            ],
        ];

        if ($this->showEmail) {
            $headers[] = [
                'id' => 'email',
                'title' => __('Email'),
                'width' => null,
                'sortable' => true,
                'sortBy' => 'email',
            ];
        }

        if ($this->showPhone) {
            $headers[] = [
                'id' => 'phone',
                'title' => __('Phone'),
                'width' => null,
                'sortable' => false,
            ];
        }

        $headers[] = [
            'id' => 'roles',
            'title' => __('Roles'),
            'width' => null,
            'sortable' => false,
        ];

        if ($this->showOrders) {
            $headers[] = [
                'id' => 'orders_count',
                'title' => __('No. of Orders'),
                'width' => null,
                'sortable' => false,
            ];
        }

        $headers[] = [
            'id' => 'created_at',
            'title' => __('Created At'),
            'width' => null,
            'sortable' => true,
            'sortBy' => 'created_at',
        ];

        $headers[] = [
            'id' => 'actions',
            'title' => __('Actions'),
            'width' => null,
            'sortable' => false,
            'is_action' => true,
        ];

        return $headers;
    }

    protected function buildQuery(): QueryBuilder
    {
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        
        $query = QueryBuilder::for($this->model)
            ->with('roles')
            ->with('userMeta')
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function renderEmailColumn(User $user): Renderable
    {
        return view('backend.pages.admins.partials.user-email', compact('user'));
    }

    public function renderPhoneColumn(User $user): string
    {
        $phone = $user->userMeta()->where('meta_key', 'phone')->first()?->meta_value ?? '';
        return '<span class="text-sm">' . e($phone) . '</span>';
    }

    public function renderOrdersCountColumn(User $user): string
    {
        // Count candidates/orders related to this staff member
        // For now, return 0 as we need to check if there's a candidates table
        // This can be updated when the relationship is established
        $count = 0; // TODO: Add relationship to count orders/candidates
        return '<span class="text-sm">' . e((string) $count) . '</span>';
    }

    public function renderNameColumn(User $user): Renderable
    {
        return view('backend.pages.users.partials.user-name', compact('user'));
    }

    public function renderRolesColumn(User $user): Renderable
    {
        return view('backend.pages.users.partials.user-roles', compact('user'));
    }

    public function renderAssignedStaffColumn(User $user): string
    {
        // Find all staff members that have this user's ID in their parent_staff_members metadata
        $assignedStaffIds = \App\Models\UserMeta::where('meta_key', 'parent_staff_members')
            ->whereRaw("FIND_IN_SET(?, meta_value) > 0", [$user->id])
            ->pluck('user_id')
            ->toArray();

        if (empty($assignedStaffIds)) {
            return '<span class="text-sm text-gray-500 dark:text-gray-400">' . __('None') . '</span>';
        }

        // Get the assigned staff members
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        $assignedStaff = User::whereIn('id', $assignedStaffIds)
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        if ($assignedStaff->isEmpty()) {
            return '<span class="text-sm text-gray-500 dark:text-gray-400">' . __('None') . '</span>';
        }

        // Display staff members as badges or comma-separated list
        $staffNames = $assignedStaff->map(function ($staff) {
            return '<span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1 mb-1">' 
                . e($staff->full_name) 
                . '</span>';
        })->implode('');

        return '<div class="flex flex-wrap gap-1">' . $staffNames . '</div>';
    }

    public function getActionCellPermissions($item): array
    {
        return [
            ...parent::getActionCellPermissions($item),
        ];
    }

    protected function handleBulkDelete(array $ids): int
    {
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        
        $ids = array_filter($ids, fn ($id) => $id != Auth::id()); // Prevent self-deletion.
        $users = User::whereIn('id', $ids)
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->get();
        $deletedCount = 0;
        foreach ($users as $user) {
            if ($user->id === Auth::id()) {
                continue;
            }

            $this->authorize('delete', $user);

            $user = $this->addHooks(
                $user,
                UserActionHook::USER_DELETED_BEFORE,
                UserFilterHook::USER_DELETED_BEFORE
            );

            $user->delete();

            $this->addHooks(
                $user,
                UserActionHook::USER_DELETED_AFTER,
                UserFilterHook::USER_DELETED_AFTER
            );

            $deletedCount++;
        }

        return $deletedCount;
    }

    public function handleRowDelete(Model|User $user): bool
    {
        // Prevent own account deletion.
        if (Auth::id() === $user->id) {
            throw new \Exception(__('You cannot delete your own account.'));
        }

        // Ensure user has one of the allowed staff roles
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        $hasAllowedRole = false;
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                $hasAllowedRole = true;
                break;
            }
        }
        
        if (!$hasAllowedRole) {
            throw new \Exception(__('This user is not a staff member.'));
        }

        $this->authorize('delete', $user);

        $user = $this->addHooks(
            $user,
            UserActionHook::USER_DELETED_BEFORE,
            UserFilterHook::USER_DELETED_BEFORE
        );

        $user->delete();

        $this->addHooks(
            $user,
            UserActionHook::USER_DELETED_AFTER,
            UserFilterHook::USER_DELETED_AFTER
        );

        return true;
    }

    protected function getPermissions(): array
    {
        return [
            'view' => 'user.view',
            'create' => 'user.create',
            'edit' => 'user.edit',
            'delete' => 'user.delete',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'create' => 'admin.staff.create',
            'edit' => 'admin.staff.edit',
            'delete' => 'admin.staff.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        return ['staff' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => $this->enableLivewireDelete ? '' : route('admin.staff.bulk-delete'),
            'method' => 'DELETE',
        ];
    }
}


