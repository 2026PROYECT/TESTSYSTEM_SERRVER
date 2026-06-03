<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    protected $fillable = [
        'assignment_id',
        'question_id',
        'response_text',
        'recording_url',
        'score',
        'comments'
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TestAssignment::class, 'assignment_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}


