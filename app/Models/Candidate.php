<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    protected $table = 'candidates';

    protected $fillable = [
        'order_id',
        'vasc_id',
        'security',
        'name',
        'surname',
        'email',
        'phone',
        'place',
        'country',
        'referensperson',
        'reference',
        'comment',
        'note',
        'cus_id',
        'interview_id',
        'status',
        'staff_id',
        'place',
        'expired',
        'hasPersonalId',
        'status',
        'referensperson',
        'reference',
        'country',
        'cv',
        'reported',
        'invoice_sent',
        'invoice_date',
        'economy',
        'criminal_record',
        'social',
        'background_checked',
        'date',
        'booked',
        'background_check_date',
        'delivery_date',
        'report',
        'report_status',
        'interview_report',
        'dep_user',
        'dep_id',
        'cus_qs_ans',
        'meta_data',
        'reported_to_sm',
        'reported_to_sm_on',
        'interview_template',
        'meta_info',
        'service_cost',
        'travel_cost',
        'basic_investigation_result',
        'BIR_interview_place',
        'combine_interview_id',
        'is_verified',
        'verified_document_path',
        'invoice_genrated',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'hasPersonalId' => 'boolean',
        'service_cost' => 'decimal:2',
        'travel_cost' => 'decimal:2',
        'booked' => 'datetime',
        'created' => 'datetime',
        'invoice_date' => 'date',
        'background_check_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cus_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'interview_id');
    }

    public function combineInterviewType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'combine_interview_id');
    }

    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status');
    }

    public function placeRelation(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
