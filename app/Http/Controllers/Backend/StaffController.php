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

class StaffController extends Controller
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

        $this->setBreadcrumbTitle(__('All Staff'));

        return $this->renderViewWithBreadcrumbs('backend.pages.staff.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', User::class);

        $this->setBreadcrumbTitle(__('New Staff'))
            ->addBreadcrumbItem(__('All Staff'), route('admin.staff.index'));

        // Get only staff roles (exclude Admin and Customer)
        $staffRoles = $this->getStaffRoles();

        // Get all staff members for the "Under this staff member" dropdown
        $allStaff = $this->getAllStaffForDropdown();

        return $this->renderViewWithBreadcrumbs('backend.pages.staff.create', [
            'roles' => $staffRoles,
            'locales' => $this->languageService->getLanguages(),
            'timezones' => $this->timezoneService->getTimezones(),
            'allStaff' => $allStaff,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        
        // Merge metadata fields from request (these are not in validation rules)
        $validated['phone'] = $request->input('phone', '');
        $validated['parent_staff_members'] = $request->input('parent_staff_members', []);
        $validated['can_upload_report'] = $request->input('can_upload_report', '0');

        $data = $this->addHooks(
            $validated,
            UserActionHook::USER_CREATED_BEFORE,
            UserFilterHook::USER_CREATED_BEFORE
        );

        // Ensure user has at least one staff role
        if (!isset($data['roles']) || empty($data['roles'])) {
            session()->flash('error', __('Staff must have at least one staff role assigned.'));
            return back();
        }

        // Validate that only staff roles are assigned
        $staffRoleNames = $this->getStaffRoleNames();
        $data['roles'] = array_filter($data['roles'], fn($role) => in_array($role, $staffRoleNames));

        if (empty($data['roles'])) {
            session()->flash('error', __('Only staff roles can be assigned to staff members.'));
            return back();
        }

        $user = $this->userService->createUserWithMetadata($data, $request);

        $user = $this->addHooks(
            $user,
            UserActionHook::USER_CREATED_AFTER,
            UserFilterHook::USER_CREATED_AFTER
        );

        session()->flash('success', __('Staff has been created.'));

        return redirect()->route('admin.staff.index');
    }

    public function edit(int $id): Renderable
    {
        $user = User::with('avatar')->findOrFail($id);

        // Ensure user is staff (not Admin or Customer)
        if (!$this->isStaffUser($user)) {
            session()->flash('error', __('This user is not a staff member.'));
            return redirect()->route('admin.staff.index');
        }

        $this->authorize('update', $user);

        $this->setBreadcrumbTitle(__('Edit Staff'))
            ->addBreadcrumbItem(__('All Staff'), route('admin.staff.index'));

        // Get only staff roles
        $staffRoles = $this->getStaffRoles();

        // Get all staff members for the "Under this staff member" dropdown (exclude current user)
        $allStaff = $this->getAllStaffForDropdown();
        // Remove current user from the list
        unset($allStaff[$user->id]);

        // Get current parent staff members from metadata
        $parentStaffMeta = $user->userMeta()->where('meta_key', 'parent_staff_members')->first();
        $parentStaffIds = $parentStaffMeta ? array_filter(explode(',', $parentStaffMeta->meta_value)) : [];

        return $this->renderViewWithBreadcrumbs('backend.pages.staff.edit', [
            'user' => $user,
            'roles' => $staffRoles,
            'allStaff' => $allStaff,
            'parentStaffIds' => $parentStaffIds,
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Ensure user is staff
        if (!$this->isStaffUser($user)) {
            session()->flash('error', __('This user is not a staff member.'));
            return redirect()->route('admin.staff.index');
        }

        $this->authorize('update', $user);

        $validated = $request->validated();
        
        // Merge metadata fields from request (these are not in validation rules)
        $validated['phone'] = $request->input('phone', '');
        $validated['parent_staff_members'] = $request->input('parent_staff_members', []);
        $validated['can_upload_report'] = $request->input('can_upload_report', '0');

        $data = $this->addHooks(
            $validated,
            UserActionHook::USER_UPDATED_BEFORE,
            UserFilterHook::USER_UPDATED_BEFORE
        );

        // Validate that only staff roles are assigned
        if (isset($data['roles']) && is_array($data['roles'])) {
            $staffRoleNames = $this->getStaffRoleNames();
            $data['roles'] = array_filter($data['roles'], fn($role) => in_array($role, $staffRoleNames));

            if (empty($data['roles'])) {
                session()->flash('error', __('Staff must have at least one staff role assigned.'));
                return back();
            }
        }

        $user = $this->userService->updateUserWithMetadata($user, $data, $request);

        $user = $this->addHooks(
            $user,
            UserActionHook::USER_UPDATED_AFTER,
            UserFilterHook::USER_UPDATED_AFTER
        );

        session()->flash('success', __('Staff has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->userService->getUserById($id);

        // Ensure user is staff
        if (!$this->isStaffUser($user)) {
            session()->flash('error', __('This user is not a staff member.'));
            return redirect()->route('admin.staff.index');
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

        session()->flash('success', __('Staff has been deleted.'));

        return back();
    }

    public function bulkDelete(BulkDeleteRequest $request): RedirectResponse
    {
        $this->authorize('bulkDelete', User::class);

        $ids = $request->validated('ids');

        if (empty($ids)) {
            return redirect()->route('admin.staff.index')
                ->with('error', __('No staff selected for deletion'));
        }

        if (in_array(Auth::id(), $ids)) {
            // Remove current user from the deletion list.
            $ids = array_filter($ids, fn ($id) => $id != Auth::id());
            session()->flash('error', __('You cannot delete your own account. Other selected staff will be processed.'));

            // If no users left to delete after filtering out current user.
            if (empty($ids)) {
                return redirect()->route('admin.staff.index')
                    ->with('error', __('No staff were deleted.'));
            }
        }

        // Filter to only staff users with allowed roles
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        $staffUsers = User::whereIn('id', $ids)
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->get();

        $ids = $staffUsers->pluck('id')->toArray();

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
            session()->flash('success', __(':count staff deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No staff were deleted. Selected staff may include protected accounts.'));
        }

        return redirect()->route('admin.staff.index');
    }

    /**
     * Get staff roles (exclude Admin and Customer)
     */
    private function getStaffRoles(): array
    {
        $allRoles = $this->rolesService->getRolesDropdown();
        $staffRoleNames = $this->getStaffRoleNames();

        return array_filter($allRoles, function ($roleName) use ($staffRoleNames) {
            return in_array($roleName, $staffRoleNames);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get staff role names (User, Manager, Manager with statistics, Moderator)
     */
    private function getStaffRoleNames(): array
    {
        return ['User', 'Manager', 'Manager with statistics', 'Moderator'];
    }

    /**
     * Check if user is a staff member (has Manager, Manager with statistics, Moderator, or User role)
     */
    private function isStaffUser(User $user): bool
    {
        $allowedRoles = ['Manager', 'Manager with statistics', 'Moderator', 'User'];
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all staff members for dropdown (only Manager, Manager with statistics, User, Moderator roles)
     */
    private function getAllStaffForDropdown(): array
    {
        $allowedRoles = ['Manager', 'Manager with statistics', 'User', 'Moderator'];
        
        return User::whereHas('roles', function ($q) use ($allowedRoles) {
            $q->whereIn('name', $allowedRoles);
        })
        ->select('id', 'name', 'email')
        ->get()
        ->mapWithKeys(function ($user) {
            return [$user->id => $user->name . ' (' . $user->email . ')'];
        })
        ->toArray();
    }
}


