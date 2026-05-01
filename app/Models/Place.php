<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = [
        'name',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'place');
    }
}
