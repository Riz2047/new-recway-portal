<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RolesService
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function getAllRoles()
    {
        return Role::all();
    }

    public function getRolesDropdown(): array
    {
        return Role::pluck('name', 'name')->toArray();
    }

    public function getPaginatedRoles(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Role::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->paginate(config('settings.default_pagination', $perPage));
    }

    public static function getPermissionsByGroupName(string $group_name): Collection
    {
        return Permission::select('name', 'id')
            ->where('group_name', $group_name)
            ->get();
    }

    /**
     * Get permissions by group
     */
    public function getPermissionsByGroup(string $groupName): ?array
    {
        return $this->permissionService->getPermissionsByGroup($groupName);
    }

    public function roleHasPermissions(Role $role, $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $role->hasPermissionTo($permission->name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new role with permissions
     */
    public function createRole(string $name, array $permissions = []): Role
    {
		/** @var Role $role */
		$role = Role::where('name', $name)->where('guard_name', 'web')->first();
		if (! $role) {
			$role = Role::create(['name' => $name, 'guard_name' => 'web']);
		}

        if (! empty($permissions)) {
            // Find existing permissions by name to avoid errors if permission doesn't exist
            $existingPermissions = Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('name')
                ->toArray();
            
            // Only sync permissions that actually exist
            if (! empty($existingPermissions)) {
                $role->syncPermissions($existingPermissions);
            }
        }

        return $role;
    }

    public function findRoleById(int $id): ?Role
    {
        $role = Role::findById($id);

        return $role instanceof Role ? $role : null;
    }

    public function findRoleByName(string $name): ?Role
    {
        $role = Role::findByName($name);

        return $role instanceof Role ? $role : null;
    }

    public function updateRole(Role $role, string $name, array $permissions = []): Role
    {
        $role->name = $name;
        $role->save();

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
            // Clear permission cache when role permissions are updated
            $this->permissionService->clearPermissionCache();
        }

        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Count users in a specific role
     *
     * @param  Role|string  $role
     */
    public function countUsersInRole($role): int
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
            if (! $role) {
                return 0;
            }
        }

        return $role->users->count();
    }

    /**
     * Get roles with user counts
     */
    public function getPaginatedRolesWithUserCount(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        // Check if we're sorting by user count.
        $sort = request()->query('sort');
        $isUserCountSort = ($sort === 'user_count' || $sort === '-user_count');

        // For user count sorting, we need to handle it separately.
        if ($isUserCountSort) {
            // Get all roles matching the search criteria without any sorting.
            $query = Role::query();

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            // Use withCount to avoid N+1 queries
            $allRoles = $query->withCount('users')->get();

            // User count is already loaded via withCount, just map it
            foreach ($allRoles as $role) {
                $role->setAttribute('user_count', $role->users_count ?? 0);
            }

            // Sort the collection by user_count.
            $direction = $sort === 'user_count' ? 'asc' : 'desc';
            $sortedRoles = $direction === 'asc'
                ? $allRoles->sortBy('user_count')
                : $allRoles->sortByDesc('user_count');

            // Manually paginate the collection.
            $page = request()->get('page', 1);
            $offset = ($page - 1) * $perPage;

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $sortedRoles->slice($offset, $perPage)->values(),
                $sortedRoles->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // For normal sorting by database columns.
        $filters = [
            'search' => $search,
            'sort_field' => 'name',
            'sort_direction' => 'asc',
        ];

        $query = Role::applyFilters($filters);
        // Use withCount to avoid N+1 queries
        $query->withCount('users');
        $roles = $query->paginateData(['per_page' => $perPage]);

        // User count is already loaded via withCount, just map it
        foreach ($roles->items() as $role) {
            $role->setAttribute('user_count', $role->users_count ?? 0);
        }

        return $roles;
    }

    /**
     * Create predefined roles with their permissions
     */
    public function createPredefinedRoles(): array
    {
        $roles = [];

        // Get all permission names for Admin role
        $allPermissionNames = [];
        foreach ($this->permissionService->getAllPermissions() as $group) {
            foreach ($group['permissions'] as $permission) {
                $allPermissionNames[] = $permission;
            }
        }

		// 1. Admin role - has almost all permissions except some critical ones.
        $adminPermissions = $allPermissionNames;
        $adminExcludedPermissions = [
            'user.delete', // Cannot delete users.
            'user.login_as', // Cannot login as other users.
        ];

        $adminPermissions = array_diff($adminPermissions, $adminExcludedPermissions);
        $roles['admin'] = $this->createRole('Admin', $adminPermissions);

		// ===== Legacy roles mapping =====
		// Note: "Staff" is a user category, not a role. Staff users are assigned one of: User, Moderator, or Manager with statistics

		// User: minimal self-service (for staff category)
		$userPermissions = [
			'dashboard.view',
			'profile.view', 'profile.edit', 'profile.update',
			'candidate.view_own',
			'post.view', 'term.view', 'media.view',
		];
		$roles['user'] = $this->createRole('User', $userPermissions);

		// Customer: customer management + order basics
		$customerPermissions = [
			'dashboard.view',
			'profile.view', 'profile.edit', 'profile.update',
			'customer.view', 'customer.create', 'customer.update',
			'order.view', 'order.create',
			'message.view',
		];
		$roles['customer'] = $this->createRole('Customer', $customerPermissions);

		// Manager: broader scope, can view all candidates, manage statuses, departments, services/places
		$managerPermissions = [
			'dashboard.view',
			'profile.view', 'profile.edit', 'profile.update',
			// Orders
			'order.view', 'order.create', 'order.update',
			// Reviewer & Interviews & Emails & History
			'reviewer.view', 'interviews.view', 'emails.view', 'history.view',
			// Department (view and manage department-user links)
			'department.view', 'department.create', 'department.update',
			'department_user.view', 'department_user.create', 'department_user.update',
			// Candidate (all scope)
			'candidate.view_all', 'candidate.view_own', 'candidate.create', 'candidate.update',
			// Status (full access)
			'status.view', 'status.change', 'status.create', 'status.update',
			// Service/Place (full access)
			'service.view', 'service.create', 'service.update',
			'place.view', 'place.create', 'place.update',
			// Message/Documentation (view)
			'message.view', 'documentation.view',
			// Logs (all)
			'logs.view_all', 'logs.view_own',
			// Statistics
			'statistics.view',
			// Media
			'media.view',
		];
		$roles['manager'] = $this->createRole('Manager', $managerPermissions);

		// Moderator: content moderation
		$moderatorPermissions = [
			'dashboard.view',
			'profile.view',
			'post.view', 'post.edit',
			'term.view',
			'media.view', 'media.edit',
			'documentation.view',
		];
		$roles['moderator'] = $this->createRole('Moderator', $moderatorPermissions);

		// Manager with statistics: same as manager (statistics already included)
		$roles['manager_with_statistics'] = $this->createRole('Manager with statistics', $managerPermissions);

        return $roles;
    }

    /**
     * Get a specific predefined role's permissions
     */
    public function getPredefinedRolePermissions(string $roleName): array
    {
        $roleName = strtolower($roleName);

        switch ($roleName) {
            case 'admin':
                // All except some critical permissions.
                $adminExcludedPermissions = [
                    'user.delete',
                ];
                $allPermissionNames = [];
                foreach ($this->permissionService->getAllPermissions() as $group) {
                    foreach ($group['permissions'] as $permission) {
                        $allPermissionNames[] = $permission;
                    }
                }

                return array_diff($allPermissionNames, $adminExcludedPermissions);

            case 'user':
                return [
                    'dashboard.view',
                    'profile.view', 'profile.edit', 'profile.update',
                    'candidate.view_own',
                    'post.view', 'term.view', 'media.view',
                ];

            case 'customer':
                return [
                    'dashboard.view',
                    'profile.view', 'profile.edit', 'profile.update',
                    'order.view', 'order.create',
                    'message.view',
                ];

            case 'manager':
                return [
                    'dashboard.view',
                    'profile.view', 'profile.edit', 'profile.update',
                    'order.view', 'order.create', 'order.update',
                    'reviewer.view', 'interviews.view', 'emails.view', 'history.view',
                    'department.view', 'department.create', 'department.update',
                    'department_user.view', 'department_user.create', 'department_user.update',
                    'candidate.view_all', 'candidate.view_own', 'candidate.create', 'candidate.update',
                    'status.view', 'status.change', 'status.create', 'status.update',
                    'service.view', 'service.create', 'service.update',
                    'place.view', 'place.create', 'place.update',
                    'message.view', 'documentation.view',
                    'logs.view_all', 'logs.view_own',
                    'statistics.view',
                    'media.view',
                ];

            case 'moderator':
                return [
                    'dashboard.view',
                    'profile.view',
                    'post.view', 'post.edit',
                    'term.view',
                    'media.view', 'media.edit',
                    'documentation.view',
                ];

            case 'manager with statistics':
                return $this->getPredefinedRolePermissions('manager');

            default:
                return [
                    'dashboard.view',
                    'profile.view',
                    'profile.edit',
                    'profile.update',
                ];
        }
    }

    /**
     * Create a new role (API wrapper)
     */
    public function create(array $data): Role
    {
        return $this->createRole($data['name'], $data['permissions'] ?? []);
    }

    /**
     * Update a role (API wrapper)
     */
    public function update(Role $role, array $data): Role
    {
        return $this->updateRole($role, $data['name'], $data['permissions'] ?? []);
    }
}
