<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateReportHtml extends Model
{
    protected $table = 'candidate_report_htmls';

    protected $fillable = [
        'candidate_id',
        'lang',
        'report_data',
    ];

    protected $casts = [
        'report_data' => 'array',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
