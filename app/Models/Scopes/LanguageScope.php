<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema; // <--- ¡ASEGÚRATE QUE ESTA LÍNEA ESTÉ!

class LanguageScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Si estamos en la consola (Tinker/Seeders), no aplicamos el filtro
        if (app()->runningInConsole()) {
            return;
        }

        $languageId = request()->header('X-Language-Id') ?? request()->query('language_id');

        if (!empty($languageId) && is_numeric($languageId)) {
            $table = $model->getTable();
            // Solo filtramos si la columna existe para evitar el error 500
            if (Schema::hasColumn($table, 'language_id')) {
                $builder->where($table . '.language_id', '=', $languageId);
            }
        }
    }
}