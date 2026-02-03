<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['id' => 1, 'title' => 'Create-order', 'user_type' => 1],
            ['id' => 2, 'title' => 'View-order', 'user_type' => 1],
            ['id' => 3, 'title' => 'Update-order', 'user_type' => 1],
            ['id' => 4, 'title' => 'View-reviewer', 'user_type' => 1],
            ['id' => 5, 'title' => 'Create-reviewer', 'user_type' => 1],
            ['id' => 7, 'title' => 'View-interviews', 'user_type' => 1],
            ['id' => 8, 'title' => 'View-emails', 'user_type' => 1],
            ['id' => 9, 'title' => 'View-history', 'user_type' => 1],
            ['id' => 10, 'title' => 'View-department', 'user_type' => 0],
            ['id' => 11, 'title' => 'Create-department', 'user_type' => 0],
            ['id' => 12, 'title' => 'Update-department', 'user_type' => 0],
            ['id' => 13, 'title' => 'View-department-user', 'user_type' => 0],
            ['id' => 14, 'title' => 'Create-department-user', 'user_type' => 0],
            ['id' => 15, 'title' => 'Update-department-user', 'user_type' => 0],
            ['id' => 16, 'title' => '3-Background-Check-Questions', 'user_type' => 0],
            ['id' => 22, 'title' => 'view_customer', 'user_type' => 3],
            ['id' => 23, 'title' => 'create_customer', 'user_type' => 3],
            ['id' => 24, 'title' => 'update_customer', 'user_type' => 3],
            ['id' => 25, 'title' => 'view_all_candidate', 'user_type' => 3],
            ['id' => 26, 'title' => 'view_own_candidate', 'user_type' => 3],
            ['id' => 27, 'title' => 'create_candidate', 'user_type' => 3],
            ['id' => 28, 'title' => 'update_candidate', 'user_type' => 3],
            ['id' => 29, 'title' => 'change_status', 'user_type' => 3],
            ['id' => 30, 'title' => 'view_status', 'user_type' => 3],
            ['id' => 31, 'title' => 'create_status', 'user_type' => 3],
            ['id' => 32, 'title' => 'update_status', 'user_type' => 3],
            ['id' => 33, 'title' => 'view_service', 'user_type' => 3],
            ['id' => 34, 'title' => 'create_service', 'user_type' => 3],
            ['id' => 35, 'title' => 'update_service', 'user_type' => 3],
            ['id' => 38, 'title' => 'view_place', 'user_type' => 3],
            ['id' => 39, 'title' => 'create_place', 'user_type' => 3],
            ['id' => 40, 'title' => 'update_place', 'user_type' => 3],
            ['id' => 41, 'title' => 'view_message', 'user_type' => 3],
            ['id' => 42, 'title' => 'update_message', 'user_type' => 3],
            ['id' => 43, 'title' => 'view_documentation', 'user_type' => 3],
            ['id' => 44, 'title' => 'update_documentation', 'user_type' => 3],
            ['id' => 45, 'title' => 'view_own_logs', 'user_type' => 3],
            ['id' => 46, 'title' => 'view_all_logs', 'user_type' => 3],
            ['id' => 47, 'title' => 'delete_logs', 'user_type' => 3],
            ['id' => 48, 'title' => 'change_staff', 'user_type' => 3],
            ['id' => 49, 'title' => 'view_statistics', 'user_type' => 3],
        ];

        foreach ($permissions as $permission) {
            DB::table('user_permissions')->updateOrInsert(
                ['id' => $permission['id']],
                $permission
            );
        }
    }
}
