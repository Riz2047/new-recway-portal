<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowedEmail extends Model
{
    protected $table = 'allowed_emails';

    protected $fillable = ['cus_id', 'allowed_status_ids'];

    protected $casts = ['allowed_status_ids' => 'array'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
}
