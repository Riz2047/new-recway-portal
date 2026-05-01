<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('allowed_emails')) {
            return;
        }

        Schema::table('allowed_emails', function (Blueprint $table): void {
            if (! Schema::hasColumn('allowed_emails', 'allowed_status_ids')) {
                $table->json('allowed_status_ids')->nullable()->after('cus_id');
            }
        });

        $grouped = DB::table('allowed_emails')
            ->select('cus_id')
            ->selectRaw('GROUP_CONCAT(CASE WHEN allowed = 1 THEN status_id END) as status_ids')
            ->groupBy('cus_id')
            ->get();

        DB::table('allowed_emails')->truncate();

        foreach ($grouped as $row) {
            $ids = empty($row->status_ids)
                ? []
                : array_values(array_unique(array_map('intval', array_filter(explode(',', (string) $row->status_ids)))));

            DB::table('allowed_emails')->insert([
                'cus_id' => (int) $row->cus_id,
                'allowed_status_ids' => json_encode($ids, JSON_THROW_ON_ERROR),
            ]);
        }

        Schema::table('allowed_emails', function (Blueprint $table): void {
            if (Schema::hasColumn('allowed_emails', 'status_id')) {
                $table->dropIndex(['cus_id', 'status_id']);
            }
        });

        Schema::table('allowed_emails', function (Blueprint $table): void {
            if (Schema::hasColumn('allowed_emails', 'status_id')) {
                $table->dropColumn('status_id');
            }
            if (Schema::hasColumn('allowed_emails', 'allowed')) {
                $table->dropColumn('allowed');
            }
            $table->unique('cus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('allowed_emails')) {
            return;
        }

        Schema::table('allowed_emails', function (Blueprint $table): void {
            if (! Schema::hasColumn('allowed_emails', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('cus_id');
            }
            if (! Schema::hasColumn('allowed_emails', 'allowed')) {
                $table->unsignedTinyInteger('allowed')->default(1)->after('status_id');
            }
        });

        $rows = DB::table('allowed_emails')->get();
        DB::table('allowed_emails')->truncate();

        foreach ($rows as $row) {
            $ids = json_decode((string) ($row->allowed_status_ids ?? '[]'), true);
            $ids = is_array($ids) ? $ids : [];

            if ($ids === []) {
                DB::table('allowed_emails')->insert([
                    'cus_id' => (int) $row->cus_id,
                    'status_id' => null,
                    'allowed' => 0,
                ]);
                continue;
            }

            foreach ($ids as $statusId) {
                DB::table('allowed_emails')->insert([
                    'cus_id' => (int) $row->cus_id,
                    'status_id' => (int) $statusId,
                    'allowed' => 1,
                ]);
            }
        }

        Schema::table('allowed_emails', function (Blueprint $table): void {
            $table->dropUnique(['cus_id']);
            if (Schema::hasColumn('allowed_emails', 'allowed_status_ids')) {
                $table->dropColumn('allowed_status_ids');
            }
            $table->index(['cus_id', 'status_id']);
        });
    }
};
