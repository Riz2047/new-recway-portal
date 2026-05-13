<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reviewer extends Model
{
    protected $table = 'reviewers';

    public $timestamps = false;

    protected $fillable = ['cus_id', 'email', 'password'];

    protected $hidden = ['password'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
}
