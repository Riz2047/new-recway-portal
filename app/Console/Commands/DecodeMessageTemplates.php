<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time utility: decode HTML entities in every messages.templates JSON.
 *
 * Converts &auml; → ä, &ouml; → ö, &aring; → å, &nbsp; → (space), etc.
 * so templates display correctly in the admin editor and in plain-text previews.
 */
class DecodeMessageTemplates extends Command
{
    protected $signature = 'messages:decode-entities';

    protected $description = 'Decode HTML entities (&auml; → ä, &nbsp; → space) in all messages.templates rows';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        DB::table('messages')->orderBy('id')->chunk(100, function ($rows) use (&$updated, &$skipped): void {
            foreach ($rows as $row) {
                $templates = json_decode((string) ($row->templates ?? '{}'), true);

                if (! is_array($templates)) {
                    $skipped++;
                    continue;
                }

                $changed = false;

                foreach ($templates as $key => $body) {
                    if (! is_string($body)) {
                        continue;
                    }

                    $decoded = $this->decodeEntities($body);

                    if ($decoded !== $body) {
                        $templates[$key] = $decoded;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('messages')
                        ->where('id', $row->id)
                        ->update(['templates' => json_encode($templates, JSON_UNESCAPED_UNICODE)]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Done. Rows updated: {$updated} | Rows already clean: {$skipped}");

        return self::SUCCESS;
    }

    private function decodeEntities(string $body): string
    {
        // Decode all named + numeric HTML entities to their UTF-8 equivalents.
        $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Replace any non-breaking space (U+00A0, from &nbsp;) with a regular space.
        $decoded = str_replace("\xc2\xa0", ' ', $decoded);

        return $decoded;
    }
}
