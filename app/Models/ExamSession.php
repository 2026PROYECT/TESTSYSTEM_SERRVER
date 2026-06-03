<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = ['exam_attempt_id', 'last_question_index', 'ip_address'];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }
    protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\LanguageScope);
}
}