<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Criterion extends Model
{
    protected $fillable = ['test_id', 'name', 'description', 'scale'];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
