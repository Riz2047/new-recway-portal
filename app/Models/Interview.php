<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Interview extends Model
{
    protected $table = 'interviews';

    protected $fillable = [
        'service_cat_id',
        'title',
        'desc',
        'country',
        'place',
        'cost',
        'delivery_days',
    ];

    /**
     * Get the service category this interview belongs to
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_cat_id');
    }

    /**
     * Get the statuses associated with this interview
     */
    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(
            Status::class,
            'status_services',
            'service_id',
            'status_id'
        )->withPivot('msg_col')->withTimestamps();
    }

    /**
     * Get customer services for this interview
     */
    public function customerServices(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_services',
            'service_id',
            'cus_id'
        )->withPivot('service_cost')->withTimestamps();
    }
}
