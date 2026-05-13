<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateHistory extends Model
{
    protected $table = 'history';

    protected $fillable = [
        'order_id',
        'desc',
        'date_time',
        'comment',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'order_id');
    }
}
