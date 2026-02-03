<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerService extends Model
{
    protected $table = 'customer_services';
    
    public $timestamps = false;
    
    protected $fillable = [
        'cus_id',
        'service_id',
        'service_cost',
    ];
    
    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
    
    /**
     * Get the service/interview
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Interview::class, 'service_id');
    }
}
