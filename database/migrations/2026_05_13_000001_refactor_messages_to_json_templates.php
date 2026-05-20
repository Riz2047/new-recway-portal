<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the 45+ sparse TEXT columns in `messages` with a single JSON `templates` column.
 *
 * Key convention in the JSON:
 *   - Numeric string key  → status_id  (e.g. "15" = the template for Status #15)
 *   - Named string key    → special template referenced directly in code
 *                           (cus_msg, admin_msg, staff_msg)
 *
 * Data migration:
 *   For each existing row we build the JSON from all non-null old columns:
 *     1. If the column is a "special key" (cus_msg / admin_msg / staff_msg) → keep as-is.
 *     2. If status_services maps this column name to a status_id for this row's
 *        interview_id → store under that status_id string.
 *     3. Otherwise → store under the original column name so no data is lost.
 */
return new class () extends Migration {
    /** Columns used directly by name in PHP code — preserved as string keys in JSON. */
    private const SPECIAL_KEYS = ['cus_msg', 'admin_msg', 'staff_msg'];

    /** All legacy TEXT columns that will be dropped after migration. */
    private const OLD_COLUMNS = [
        'cus_msg', 'can_msg', 'can_msg_2', 'admin_msg', 'staff_msg',
        'pending_msg', 'booked_msg', 'approved_msg', 'approved_msg_2',
        'invest_msg', 'spo_msg', 'denied_msg', 'denied_msg_2',
        'notshow_msg', 'canceled_msg', 'noans_msg',
        'staff_cancel', 'can_cancel',
        'cus_msg_background', 'can_msg_background', 'pending_background',
        'consent_msg', 'approval_received_msg', 'research_started_msg', 'results_received_msg',
        'REbook_interviews', 'deviation', 'approved_msg_bc',
        'still_not_booked_msg', 'not_available_msg',
        'person_startedf', 'candidate_msg',
        'Pending', 'Booked', 'Candidatedidntshowup', 'Approved_followup',
        'Candidate_Cancel', 'follow_up_under_investigation', 'bk_can_cancel',
        'Candidate_interup_followup', 'denid_followup', 'still_not_booked_followup',
        'Contact_established', 'no_avaible_followup',
        'Customer_flow', 'withoutdeviation', 'Cus_msg_exit',
    ];

    public function up(): void
    {
        // ── Step 1: add the JSON column ──────────────────────────────────────────
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'templates')) {
                $table->json('templates')->nullable()->after('interview_id');
            }
        });

        // ── Step 2: build msg_col → status_id map per service_id ─────────────────
        // Shape: [ service_id => [ 'pending_msg' => 15, 'booked_msg' => 8, ... ] ]
        $colToStatusByService = [];

        if (Schema::hasTable('status_services')) {
            DB::table('status_services')
                ->select('service_id', 'status_id', 'msg_col')
                ->whereNotNull('msg_col')
                ->where('msg_col', '!=', '')
                ->orderBy('status_id')   // deterministic when duplicates exist
                ->get()
                ->each(function ($row) use (&$colToStatusByService): void {
                    // Last status_id wins if two statuses share the same msg_col+service.
                    $colToStatusByService[$row->service_id][$row->msg_col] = $row->status_id;
                });
        }

        // ── Step 3: migrate each messages row ────────────────────────────────────
        DB::table('messages')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($colToStatusByService): void {
                foreach ($rows as $row) {
                    $templates = [];
                    $colMapping = $colToStatusByService[$row->interview_id] ?? [];

                    foreach (self::OLD_COLUMNS as $col) {
                        $value = $row->{$col} ?? null;
                        if ($value === null || $value === '') {
                            continue;
                        }

                        if (in_array($col, self::SPECIAL_KEYS, true)) {
                            // Always preserve special keys under their original name.
                            $templates[$col] = $value;
                        } elseif (isset($colMapping[$col])) {
                            // Map column name → status_id (numeric string key).
                            $templates[(string) $colMapping[$col]] = $value;
                        } else {
                            // No mapping found — keep original name so no data is lost.
                            $templates[$col] = $value;
                        }
                    }

                    DB::table('messages')
                        ->where('id', $row->id)
                        ->update(['templates' => json_encode($templates, JSON_UNESCAPED_UNICODE)]);
                }
            });

        // ── Step 4: drop all old TEXT columns ────────────────────────────────────
        // Drop in batches to avoid hitting column-drop limits on some MySQL versions.
        $batches = array_chunk(self::OLD_COLUMNS, 10);
        foreach ($batches as $batch) {
            $existing = array_filter(
                $batch,
                fn ($col) => Schema::hasColumn('messages', $col)
            );
            if ($existing) {
                Schema::table('messages', function (Blueprint $table) use ($existing): void {
                    $table->dropColumn(array_values($existing));
                });
            }
        }

        // ── Step 5: upgrade the existing composite index to UNIQUE ───────────────
        // The old migration added a non-unique index; replace it with a unique one.
        // Use Schema::getIndexes() — the Laravel 11+ / 12 native API (no Doctrine).
        $existingIndexNames = array_column(Schema::getIndexes('messages'), 'name');

        Schema::table('messages', function (Blueprint $table) use ($existingIndexNames): void {
            $oldIndex = 'messages_cus_id_interview_id_index';
            $uniqueIndex = 'messages_cus_id_interview_id_unique';

            if (in_array($oldIndex, $existingIndexNames, true)) {
                $table->dropIndex($oldIndex);
            }

            if (! in_array($uniqueIndex, $existingIndexNames, true)) {
                $table->unique(['cus_id', 'interview_id']);
            }
        });

        // ── Step 6: drop msg_col from status_services ─────────────────────────────
        if (Schema::hasTable('status_services') && Schema::hasColumn('status_services', 'msg_col')) {
            Schema::table('status_services', function (Blueprint $table): void {
                $table->dropColumn('msg_col');
            });
        }
    }

    public function down(): void
    {
        // Restore msg_col in status_services
        if (Schema::hasTable('status_services') && ! Schema::hasColumn('status_services', 'msg_col')) {
            Schema::table('status_services', function (Blueprint $table): void {
                $table->string('msg_col')->nullable()->after('service_id');
            });
        }

        // Restore old TEXT columns (empty — data already in JSON)
        Schema::table('messages', function (Blueprint $table): void {
            foreach (self::OLD_COLUMNS as $col) {
                if (! Schema::hasColumn('messages', $col)) {
                    $table->text($col)->nullable();
                }
            }
        });

        // Drop templates column
        if (Schema::hasColumn('messages', 'templates')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->dropColumn('templates');
            });
        }
    }
};
