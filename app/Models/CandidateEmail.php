<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEmail extends Model
{
    protected $table = 'emails';

    protected $fillable = [
        'user_type',
        'user_name',
        'order_id',
        'msg_type',
        'text',
        'email',
        'subject',
        'email_delay',
    ];
}
