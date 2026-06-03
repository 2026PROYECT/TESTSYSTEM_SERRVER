<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulesQuiz extends Model
{
    protected $table = 'modules_quiz';

    protected $fillable = [
        'module_id',
        'quiz_id',
        'order_position'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}