<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedPdfCandidate extends Model
{
    protected $table = 'uploaded_pdf_candidate';

    protected $fillable = [
        'can_id',
        'file_name',
        'file_for',
        'is_trash',
    ];

    public const FOR_ECONOMY = 1;
    public const FOR_CRIMINAL = 2;
    public const FOR_SOCIAL = 3;

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'can_id');
    }

    public function getForLabelAttribute(): string
    {
        return match ((int) $this->file_for) {
            self::FOR_ECONOMY => 'Economy',
            self::FOR_CRIMINAL => 'Criminal Record',
            self::FOR_SOCIAL => 'Social Media',
            default => 'Unknown',
        };
    }
}
