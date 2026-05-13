<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot between statuses and service_types (interviews).
 * Stores the `msg_col` name — the column in `messages` that holds the email template
 * for this status + service combination.
 */
class StatusServiceLink extends Model
{
    protected $table = 'status_services';

    protected $fillable = [
        'status_id',
        'service_id',
        'msg_col',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_id');
    }
}
