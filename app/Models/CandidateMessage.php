<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a row from the `messages` table (one row per customer + service type).
 *
 * Email template bodies are stored in the `templates` JSON column.
 * Key convention: the key is the msg_col value from status_services
 * (e.g. 'approved_msg', 'pending_msg') or a fixed special key
 * ('cus_msg', 'admin_msg', 'staff_msg').
 */
class CandidateMessage extends Model
{
    protected $table = 'messages';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'templates' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'interview_id');
    }

    /**
     * Get the email template body for a given key.
     *
     * Key is the msg_col value (e.g. 'approved_msg') or a special key
     * ('cus_msg', 'admin_msg', 'staff_msg').
     * Returns null when the key does not exist or the body is empty.
     */
    public function getBodyForKey(string $key): ?string
    {
        $body = ($this->templates ?? [])[$key] ?? null;

        return ($body !== null && $body !== '') ? (string) $body : null;
    }

    /**
     * Write (or clear) a single template entry and persist immediately.
     * Merges into the existing JSON rather than overwriting the whole column.
     */
    public function setTemplate(string $key, ?string $body): void
    {
        $templates = $this->templates ?? [];
        $templates[$key] = ($body !== null && $body !== '') ? $body : null;

        $this->templates = $templates;
        $this->save();
    }
}
