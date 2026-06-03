<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Usamos updateOrCreate para que no duplique registros si lo corres varias veces
        Setting::updateOrCreate(
            ['key' => 'allowed_email_domain'],
            ['value' => 'est.emi.edu.bo']
        );
    }
}