<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'verifiable_type',
        'verifiable_id',
        'type',
        'metadata',
        'scans_count',
        'expires_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }
}