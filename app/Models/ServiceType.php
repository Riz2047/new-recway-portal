<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = [
        'service_category_id',
        'name',
        'price',
        'description',
        'place',
        'country',
        'delivery_days',
    ];

    protected function casts(): array
    {
        return [
            'place' => 'boolean',
            'country' => 'boolean',
        ];
    }

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
        return $this->belongsToMany(Customer::class, 'service_type_user', 'service_type_id', 'cus_id')->withTimestamps();
    }

    /**
     * Candidates/orders linked to this service type.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'interview_id');
    }
}
