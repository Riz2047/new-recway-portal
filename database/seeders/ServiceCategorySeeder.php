<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('service_categories')->truncate();

        // IDs must match the old system exactly: 1, 3, 9, 10 (with gaps).
        // status_type FK in statuses, and service_category_id FK in service_types both reference these.
        DB::table('service_categories')->insert([
            ['id' => 1,  'name' => 'Interviews',           'name_sv' => 'Intervjuer',              'created_at' => now(), 'updated_at' => now()],
            ['id' => 3,  'name' => 'Background Check',     'name_sv' => 'Bakgrundskontroll',       'created_at' => now(), 'updated_at' => now()],
            ['id' => 9,  'name' => 'Follow-up - Interview','name_sv' => 'Uppföljning - Intervju',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Exit Interview',       'name_sv' => 'Avslutningsintervju',     'created_at' => now(), 'updated_at' => now()],
        ]);

        // Advance AUTO_INCREMENT past the highest known id (was 13 in old system).
        DB::statement('ALTER TABLE service_categories AUTO_INCREMENT = 13');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
