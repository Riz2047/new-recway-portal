<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyManager extends Model
{
    protected $table = 'company_manager';
    public $timestamps = false;

    protected $fillable = [
        'cus_id',
        'company',
        'statuses',
        'can_view_report',
        'email_template',
        'email_template_approved',
    ];

    protected function casts(): array
    {
        return [
            'can_view_report' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }
}
