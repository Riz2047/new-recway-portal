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

class AdminDatatable extends Datatable
{
    public bool $showEmail = true;
    public array $queryString = [
        ...parent::QUERY_STRING_DEFAULTS,
        'showEmail' => ['except' => true],
    ];
    public string $model = User::class;
    public array $disabledRoutes = ['view'];
    public string $newResourceLinkRouteName = 'admin.admins.create';
    public string $newResourceLinkPermission = 'user.create';
    public string $newResourceLinkLabel = '';
    public string $newResourceLinkIcon = 'lucide:user-plus';
    public string $actionColumnIcon = 'lucide:settings';

    public function mount(): void
    {
        parent::mount();
        $this->showEmail = session('admin_datatable_show_email', true);
        $this->setActionLabels();
        $this->newResourceLinkLabel = __('New Admin');
        $this->dispatch('email-column-updated', showEmail: $this->showEmail);
    }

    public function updatedShowEmail($value): void
    {
        session(['admin_datatable_show_email' => $value]);
        $this->resetPage();
        $this->dispatch('email-column-updated', showEmail: $this->showEmail);
    }

    public function toggleEmailColumn(): void
    {
        $this->showEmail = !$this->showEmail;
        $this->updatedShowEmail($this->showEmail);
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
                'sortBy' => 'name',
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
        $query = QueryBuilder::for($this->model)
            ->with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            });

        return $this->sortQuery($query);
    }

    public function renderNameColumn(User $user): Renderable
    {
        return view('backend.pages.users.partials.user-name', compact('user'));
    }

    public function renderEmailColumn(User $user): Renderable
    {
        return view('backend.pages.admins.partials.user-email', compact('user'));
    }


    public function getActionCellPermissions($item): array
    {
        return [
            ...parent::getActionCellPermissions($item),
        ];
    }

    protected function handleBulkDelete(array $ids): int
    {
        $ids = array_filter($ids, fn ($id) => $id != Auth::id()); // Prevent self-deletion.
        $users = User::whereIn('id', $ids)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
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
            'create' => 'admin.admins.create',
            'edit' => 'admin.admins.edit',
            'delete' => 'admin.admins.destroy',
        ];
    }

    protected function getItemRouteParameters($item): array
    {
        // Override to use 'admin' parameter name instead of 'user'
        return ['admin' => $item->id];
    }

    public function getBulkDeleteAction(): array
    {
        return [
            'url' => $this->enableLivewireDelete ? '' : route('admin.admins.bulk-delete'),
            'method' => 'DELETE',
        ];
    }
}

