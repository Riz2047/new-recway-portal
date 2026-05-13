<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a row from the `messages` table (per customer + service type).
 * Each column (booked_msg, approved_msg, etc.) is an HTML email body template.
 */
class CandidateMessage extends Model
{
    protected $table = 'messages';

    public $timestamps = false;

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'interview_id');
    }

    /**
     * Get the message body for a given column name (e.g. 'booked_msg').
     * Returns null if the column does not exist or is empty.
     */
    public function getBodyForColumn(string $column): ?string
    {
        if (! in_array($column, $this->getConnection()->getSchemaBuilder()->getColumnListing($this->table), true)) {
            return null;
        }

        $value = $this->getAttribute($column);

        return ! empty($value) ? (string) $value : null;
    }
}
