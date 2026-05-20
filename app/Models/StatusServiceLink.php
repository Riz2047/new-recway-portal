<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot between statuses and service_types (interviews).
 * msg_col is the key used in messages.templates JSON for this status+service combination.
 * e.g. msg_col = 'approved_msg' → templates['approved_msg'] = '<html>…</html>'
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
