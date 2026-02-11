<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    protected $fillable = [
        'name',
        'name_sv',
    ];

    /**
     * Get the statuses for this service category
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class, 'status_type');
    }

    /**
     * Get the service types for this category
     */
    public function serviceTypes(): HasMany
    {
        return $this->hasMany(ServiceType::class);
    }
}
