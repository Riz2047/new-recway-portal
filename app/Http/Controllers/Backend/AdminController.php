<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\Hooks\UserActionHook;
use App\Enums\Hooks\UserFilterHook;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\Common\BulkDeleteRequest;
use App\Models\User;
use App\Models\Role;
use App\Services\LanguageService;
use App\Services\RolesService;
use App\Services\TimezoneService;
use App\Services\UserService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RolesService $rolesService,
        private readonly LanguageService $languageService,
        private readonly TimezoneService $timezoneService,
    ) {
    }

    public function index(): Renderable
    {
        $this->authorize('viewAny', User::class);

        $this->setBreadcrumbTitle(__('Admins'));

        return $this->renderViewWithBreadcrumbs('backend.pages.admins.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', User::class);

        $this->setBreadcrumbTitle(__('New Admin'))
            ->addBreadcrumbItem(__('Admins'), route('admin.admins.index'));

        // Show all roles so user can assign multiple roles (Admin will be default/required)
        return $this->renderViewWithBreadcrumbs('backend.pages.admins.create', [
            'roles' => $this->rolesService->getRolesDropdown(),
            'locales' => $this->languageService->getLanguages(),
            'timezones' => $this->timezoneService->getTimezones(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->addHooks(
            $request->validated(),
            UserActionHook::USER_CREATED_BEFORE,
            UserFilterHook::USER_CREATED_BEFORE
        );

        // Ensure Admin role is assigned (default), but allow other roles to be added
        if (!isset($data['roles'])) {
            $data['roles'] = [];
        }
        
        // Add Admin role if not already present (default role for admin creation)
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole && !in_array('Admin', $data['roles'])) {
            $data['roles'][] = 'Admin';
        }
        // Allow other roles (Staff, Customer) to be selected during creation

        $user = $this->userService->createUserWithMetadata($data, $request);

        $user = $this->addHooks(
            $user,
            UserActionHook::USER_CREATED_AFTER,
            UserFilterHook::USER_CREATED_AFTER
        );

        session()->flash('success', __('Admin has been created.'));

        return redirect()->route('admin.admins.index');
    }

    public function edit(int $id): Renderable
    {
        $user = User::with('avatar')->findOrFail($id);

        // Ensure user has Admin role
        if (!$user->hasRole('Admin')) {
            session()->flash('error', __('This user is not an admin.'));
            return redirect()->route('admin.admins.index');
        }

        $this->authorize('update', $user);

        $this->setBreadcrumbTitle(__('Edit Admin'))
            ->addBreadcrumbItem(__('Admins'), route('admin.admins.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.admins.edit', [
            'user' => $user,
            'roles' => $this->rolesService->getRolesDropdown(),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        
        // Ensure user has Admin role
        if (!$user->hasRole('Admin')) {
            session()->flash('error', __('This user is not an admin.'));
            return redirect()->route('admin.admins.index');
        }

        $this->authorize('update', $user);

        $data = $this->addHooks(
            $request->validated(),
            UserActionHook::USER_UPDATED_BEFORE,
            UserFilterHook::USER_UPDATED_BEFORE
        );

        // Ensure Admin role is maintained, but allow adding other roles (Staff, Customer) for this specific user
        if (isset($data['roles']) && is_array($data['roles'])) {
            if (!in_array('Admin', $data['roles'])) {
                $data['roles'][] = 'Admin';
            }
            // Allow other roles to be added - user can have Admin + Staff + Customer roles
        }

        $user = $this->userService->updateUserWithMetadata($user, $data, $request);

        $user = $this->addHooks(
            $user,
            UserActionHook::USER_UPDATED_AFTER,
            UserFilterHook::USER_UPDATED_AFTER
        );

        session()->flash('success', __('Admin has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->userService->getUserById($id);

        // Ensure user has Admin role
        if (!$user->hasRole('Admin')) {
            session()->flash('error', __('This user is not an admin.'));
            return redirect()->route('admin.admins.index');
        }

        // Check if user is trying to delete themselves.
        if (Auth::id() === $user->id) {
            session()->flash('error', __('You cannot delete your own account.'));
            return back();
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

        session()->flash('success', __('Admin has been deleted.'));

        return back();
    }

    public function bulkDelete(BulkDeleteRequest $request): RedirectResponse
    {
        $this->authorize('bulkDelete', User::class);

        $ids = $request->validated('ids');

        if (empty($ids)) {
            return redirect()->route('admin.admins.index')
                ->with('error', __('No admins selected for deletion'));
        }

        if (in_array(Auth::id(), $ids)) {
            // Remove current user from the deletion list.
            $ids = array_filter($ids, fn ($id) => $id != Auth::id());
            session()->flash('error', __('You cannot delete your own account. Other selected admins will be processed.'));

            // If no users left to delete after filtering out current user.
            if (empty($ids)) {
                return redirect()->route('admin.admins.index')
                    ->with('error', __('No admins were deleted.'));
            }
        }

        // Filter to only admins
        $adminUsers = User::whereIn('id', $ids)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })
            ->get();

        $ids = $adminUsers->pluck('id')->toArray();

        $this->addHooks(
            $ids,
            UserActionHook::USER_BULK_DELETED_BEFORE,
            UserFilterHook::USER_BULK_DELETED_BEFORE
        );

        $deletedCount = $this->userService->bulkDeleteUsers($ids, Auth::id());

        $this->addHooks(
            $deletedCount,
            UserActionHook::USER_BULK_DELETED_AFTER,
            UserFilterHook::USER_BULK_DELETED_AFTER
        );

        if ($deletedCount > 0) {
            session()->flash('success', __(':count admins deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No admins were deleted. Selected admins may include protected accounts.'));
        }

        return redirect()->route('admin.admins.index');
    }
}

