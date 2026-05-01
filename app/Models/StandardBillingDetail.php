<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardBillingDetail extends Model
{
    protected $table = 'standard_billing_details';

    protected $fillable = [
        'cus_id',
        'referenceperson',
        'reference',
        'comment',
        // add your actual columns here
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
}
