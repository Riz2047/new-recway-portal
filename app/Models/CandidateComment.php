<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateComment extends Model
{
    protected $table = 'comments';

    protected $fillable = [
        'order_id',
        'author_id',
        'author_type',
        'comment',
        'read_by_admin',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'order_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
