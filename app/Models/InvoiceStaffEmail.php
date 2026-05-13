<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceStaffEmail extends Model
{
    protected $table = 'invoice_staff_email';

    protected $fillable = [
        'order_id',
        'staff_id',
        'invoice_email',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'order_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
