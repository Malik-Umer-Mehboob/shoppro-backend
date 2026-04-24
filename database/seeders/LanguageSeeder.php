<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'en',
                'locale' => 'en_US',
                'direction' => 'ltr',
                'is_active' => true,
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'exchange_rate' => 1.0,
                'is_default' => true,
            ],
            [
                'name' => 'Español',
                'code' => 'es',
                'locale' => 'es_ES',
                'direction' => 'ltr',
                'is_active' => true,
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'exchange_rate' => 0.92,
                'is_default' => false,
            ],
            [
                'name' => 'Français',
                'code' => 'fr',
                'locale' => 'fr_FR',
                'direction' => 'ltr',
                'is_active' => true,
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'exchange_rate' => 0.92,
                'is_default' => false,
            ],
            [
                'name' => 'العربية',
                'code' => 'ar',
                'locale' => 'ar_SA',
                'direction' => 'rtl',
                'is_active' => true,
                'currency_code' => 'SAR',
                'currency_symbol' => '﷼',
                'exchange_rate' => 3.75,
                'is_default' => false,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang);
        }
    }
}
