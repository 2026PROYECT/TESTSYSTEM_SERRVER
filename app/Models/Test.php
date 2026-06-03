<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    protected $fillable = ['name', 'description'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TestAssignment::class);
    }
}
