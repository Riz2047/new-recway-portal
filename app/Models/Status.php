<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Status extends Model
{
    protected $fillable = [
        'variable',
        'status',
        'status_sv',
        'status_detail',
        'status_icon',
        'color',
        'status_type',
    ];

    /**
     * Get the service category this status belongs to
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'status_type');
    }

    /**
     * Get the services/interviews associated with this status
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Interview::class,
            'status_services',
            'status_id',
            'service_id'
        )->withPivot('msg_col')->withTimestamps();
    }
}
