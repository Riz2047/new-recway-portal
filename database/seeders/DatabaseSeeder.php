<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            // ContentSeeder::class,

            // Reference data — must run in this order (FKs: categories → types/statuses → services)
            ServiceCategorySeeder::class,
            ServiceTypeSeeder::class,
            StatusSeeder::class,
            StatusServiceSeeder::class,
            DefaultMessageTemplateSeeder::class,

            EmailTemplateSeeder::class,
        ]);
    }
}
