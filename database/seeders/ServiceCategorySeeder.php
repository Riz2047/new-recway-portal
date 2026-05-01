<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('service_categories')->insert([
            [
                'id' => 1,
                'name' => 'Interviews',
                'name_sv' => 'Intervjuer',
                'created_at' => Carbon::parse('2026-03-26 15:24:11'),
                'updated_at' => Carbon::parse('2026-03-26 15:24:11'),
            ],
            [
                'id' => 2,
                'name' => 'Background Check',
                'name_sv' => 'Bakgrundskontroll',
                'created_at' => Carbon::parse('2026-03-26 15:24:31'),
                'updated_at' => Carbon::parse('2026-03-26 15:24:31'),
            ],
            [
                'id' => 3,
                'name' => 'Follow-up - Interview',
                'name_sv' => 'Uppföljning - Intervju',
                'created_at' => Carbon::parse('2026-03-26 15:24:53'),
                'updated_at' => Carbon::parse('2026-03-26 15:24:53'),
            ],
            [
                'id' => 4,
                'name' => 'Exit Interview',
                'name_sv' => 'Avslutningsintervju',
                'created_at' => Carbon::parse('2026-03-26 15:25:15'),
                'updated_at' => Carbon::parse('2026-03-26 15:25:15'),
            ],
        ]);
    }
}
