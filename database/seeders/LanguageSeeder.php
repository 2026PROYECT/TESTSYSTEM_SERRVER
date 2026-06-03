<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use Illuminate\Support\Str; // <--- No olvides importar esta clase

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'Inglés', 'code' => 'en'],
            ['name' => 'Francés', 'code' => 'fr'],
            ['name' => 'Portugués', 'code' => 'pt'],
            ['name' => 'Alemán', 'code' => 'de'],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(
                ['code' => $lang['code']], // Buscamos por código para no duplicar
                [
                    'name' => $lang['name'],
                    'slug' => Str::slug($lang['name']), // <--- Genera el slug automáticamente
                    'is_active' => true
                ]
            );
        }
    }
}