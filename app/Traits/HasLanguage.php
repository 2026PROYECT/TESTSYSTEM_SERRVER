<?php

namespace App\Traits;

use App\Models\Scopes\LanguageScope;

trait HasLanguage
{
    public static function bootHasLanguage()
    {
        static::addGlobalScope(new LanguageScope);

        static::creating(function ($model) {
            $languageId = request()->header('X-Language-Id') ?? request()->query('language_id');
            if (empty($model->language_id) && $languageId) {
                $model->language_id = $languageId;
            }
        });
    }
}