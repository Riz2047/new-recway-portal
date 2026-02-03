<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPermissionService
{
    /**
     * Get all permissions for a customer
     * 
     * @param int $customerId
     * @return array Array of permission titles as keys
     */
    public function getCustomerPermissions(int $customerId): array
    {
        if (!Schema::hasTable('user_allowed_permissions') || !Schema::hasTable('user_permissions')) {
            return [];
        }

        $permissions = DB::table('user_allowed_permissions')
            ->join('user_permissions', 'user_allowed_permissions.per_id', '=', 'user_permissions.id')
            ->where('user_allowed_permissions.user_id', $customerId)
            ->where('user_allowed_permissions.user_type', 2) // 2 = customer
            ->pluck('user_permissions.title')
            ->toArray();

        // Convert to associative array with permission title as key (like old system)
        $permissionArray = [];
        foreach ($permissions as $permission) {
            $permissionArray[$permission] = 1;
        }

        return $permissionArray;
    }

    /**
     * Check if customer has a specific permission
     * 
     * @param int $customerId
     * @param string $permissionTitle
     * @return bool
     */
    public function hasPermission(int $customerId, string $permissionTitle): bool
    {
        $permissions = $this->getCustomerPermissions($customerId);
        return isset($permissions[$permissionTitle]) && $permissions[$permissionTitle] == 1;
    }

    /**
     * Check if customer has any of the given permissions
     * 
     * @param int $customerId
     * @param array $permissionTitles
     * @return bool
     */
    public function hasAnyPermission(int $customerId, array $permissionTitles): bool
    {
        $permissions = $this->getCustomerPermissions($customerId);
        
        foreach ($permissionTitles as $title) {
            if (isset($permissions[$title]) && $permissions[$title] == 1) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if customer has all of the given permissions
     * 
     * @param int $customerId
     * @param array $permissionTitles
     * @return bool
     */
    public function hasAllPermissions(int $customerId, array $permissionTitles): bool
    {
        $permissions = $this->getCustomerPermissions($customerId);
        
        foreach ($permissionTitles as $title) {
            if (!isset($permissions[$title]) || $permissions[$title] != 1) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get permission IDs for a customer
     * 
     * @param int $customerId
     * @return array Array of permission IDs
     */
    public function getCustomerPermissionIds(int $customerId): array
    {
        if (!Schema::hasTable('user_allowed_permissions')) {
            return [];
        }

        return DB::table('user_allowed_permissions')
            ->where('user_id', $customerId)
            ->where('user_type', 2) // 2 = customer
            ->pluck('per_id')
            ->toArray();
    }
}

