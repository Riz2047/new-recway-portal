<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuestion extends Model
{
    protected $table = 'customer_questions';

    protected $fillable = [
        'cus_id',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
}
