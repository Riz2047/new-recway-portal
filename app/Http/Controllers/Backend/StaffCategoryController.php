<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\PermissionService;
use App\Services\RolesService;
use App\Enums\Hooks\RoleActionHook;
use App\Enums\Hooks\RoleFilterHook;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class StaffCategoryController extends Controller
{
    public function __construct(
        private readonly RolesService $rolesService,
        private readonly PermissionService $permissionService
    ) {
    }

    public function index(): Renderable
    {
        $this->authorize('viewAny', Role::class);

        $this->setBreadcrumbTitle(__('Staff Category'));

        return $this->renderViewWithBreadcrumbs('backend.pages.staff-category.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', Role::class);

        $this->setBreadcrumbTitle(__('New Staff Category'))
            ->addBreadcrumbItem(__('Staff Category'), route('admin.staff-category.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.staff-category.create', [
            'roleService' => $this->rolesService,
            'all_permissions' => $this->permissionService->getAllPermissionModels(),
            'permission_groups' => $this->permissionService->getDatabasePermissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $this->addHooks(
            $request->validated(),
            RoleActionHook::ROLE_CREATED_BEFORE,
            RoleFilterHook::ROLE_CREATED_BEFORE
        );

        $role = $this->rolesService->createRole($data['name'] ?? $request->name, $data['permissions'] ?? $request->input('permissions', []));

        $role = $this->addHooks(
            $role,
            RoleActionHook::ROLE_CREATED_AFTER,
            RoleFilterHook::ROLE_CREATED_AFTER
        );

        session()->flash('success', __('Staff category has been created.'));

        return redirect()->route('admin.staff-category.index');
    }

    public function edit(int $id): Renderable|RedirectResponse
    {
        $role = $this->rolesService->findRoleById($id);
        if (! $role) {
            session()->flash('error', __('Staff category not found.'));

            return back();
        }

        // Ensure it's a staff category (not Admin or Customer)
        if ($role->name === Role::ADMIN || $role->name === 'Customer') {
            session()->flash('error', __('This is not a staff category.'));
            return redirect()->route('admin.staff-category.index');
        }

        $this->authorize('update', $role);

        $this->setBreadcrumbTitle(__('Edit Staff Category'))
            ->addBreadcrumbItem(__('Staff Category'), route('admin.staff-category.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.staff-category.edit', [
            'role' => $role,
            'roleService' => $this->rolesService,
            'all_permissions' => $this->permissionService->getAllPermissionModels(),
            'permission_groups' => $this->permissionService->getDatabasePermissionGroups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): RedirectResponse
    {
        $role = $this->rolesService->findRoleById($id);

        if (! $role) {
            session()->flash('error', __('Staff category not found.'));

            return back();
        }

        // Ensure it's a staff category
        if ($role->name === Role::ADMIN || $role->name === 'Customer') {
            session()->flash('error', __('This is not a staff category.'));
            return redirect()->route('admin.staff-category.index');
        }

        // Check if this is a protected role in demo mode
        if (config('app.demo_mode') && in_array($role->name, ['User', 'Manager', 'Moderator', 'Manager with statistics'])) {
            abort(403, 'Cannot modify protected staff categories in demo mode.');
        }

        $this->authorize('update', $role);

        $data = $this->addHooks(
            $request->validated(),
            RoleActionHook::ROLE_UPDATED_BEFORE,
            RoleFilterHook::ROLE_UPDATED_BEFORE
        );

        $role = $this->rolesService->updateRole($role, $data['name'] ?? $request->name, $data['permissions'] ?? $request->input('permissions', []));

        $role = $this->addHooks(
            $role,
            RoleActionHook::ROLE_UPDATED_AFTER,
            RoleFilterHook::ROLE_UPDATED_AFTER
        );

        session()->flash('success', __('Staff category has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = $this->rolesService->findRoleById($id);

        if (! $role) {
            session()->flash('error', __('Staff category not found.'));

            return back();
        }

        // Ensure it's a staff category
        if ($role->name === Role::ADMIN || $role->name === 'Customer') {
            session()->flash('error', __('This is not a staff category.'));
            return redirect()->route('admin.staff-category.index');
        }

        // Check if this is a protected role in demo mode
        if (config('app.demo_mode') && in_array($role->name, ['User', 'Manager', 'Moderator', 'Manager with statistics'])) {
            abort(403, 'Cannot delete protected staff categories in demo mode.');
        }

        $this->authorize('delete', $role);

        $role = $this->addHooks(
            $role,
            RoleActionHook::ROLE_DELETED_BEFORE,
            RoleFilterHook::ROLE_DELETED_BEFORE
        );

        $this->rolesService->deleteRole($role);

        $this->addHooks(
            $role,
            RoleActionHook::ROLE_DELETED_AFTER,
            RoleFilterHook::ROLE_DELETED_AFTER
        );

        session()->flash('success', __('Staff category has been deleted.'));

        return redirect()->route('admin.staff-category.index');
    }

    /**
     * Delete multiple staff categories at once
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('bulkDelete', Role::class);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.staff-category.index')
                ->with('error', __('No staff categories selected for deletion'));
        }

        $ids = $this->addHooks(
            $ids,
            RoleActionHook::ROLE_BULK_DELETED_BEFORE,
            RoleFilterHook::ROLE_BULK_DELETED_BEFORE
        );

        $deletedCount = 0;

        foreach ($ids as $id) {
            $role = $this->rolesService->findRoleById((int) $id);
            if (! $role) {
                continue;
            }
            // Skip Admin and Customer roles.
            if ($role->name === Role::ADMIN || $role->name === 'Customer') {
                continue;
            }
            // Skip protected roles in demo mode
            if (config('app.demo_mode') && in_array($role->name, ['User', 'Manager', 'Moderator', 'Manager with statistics'])) {
                continue;
            }
            $this->rolesService->deleteRole($role);
            $deletedCount++;
        }

        $deletedCount = $this->addHooks(
            $deletedCount,
            RoleActionHook::ROLE_BULK_DELETED_AFTER,
            RoleFilterHook::ROLE_BULK_DELETED_AFTER
        );

        if ($deletedCount > 0) {
            session()->flash('success', __(':count staff categories deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No staff categories were deleted. Selected categories may include protected roles.'));
        }

        return redirect()->route('admin.staff-category.index');
    }
}


