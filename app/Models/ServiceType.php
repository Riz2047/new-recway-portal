<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceType extends Model
{
    protected $fillable = [
        'service_category_id',
        'name',
        'price',
        'description',
    ];

    /**
     * Get the service category that owns the service type.
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * The customers that belong to the service type.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_type_user', 'service_type_id', 'user_id');
    }
}
