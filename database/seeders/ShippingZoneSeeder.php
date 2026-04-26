<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            ['city' => 'Karachi',   'region' => 'Sindh',    'delivery_charge' => 150, 'estimated_days' => 2],
            ['city' => 'Lahore',    'region' => 'Punjab',   'delivery_charge' => 150, 'estimated_days' => 2],
            ['city' => 'Islamabad', 'region' => 'Capital',  'delivery_charge' => 200, 'estimated_days' => 3],
            ['city' => 'Rawalpindi','region' => 'Punjab',   'delivery_charge' => 200, 'estimated_days' => 3],
            ['city' => 'Peshawar',  'region' => 'KPK',      'delivery_charge' => 250, 'estimated_days' => 4],
            ['city' => 'Quetta',    'region' => 'Balochistan','delivery_charge' => 300,'estimated_days' => 5],
            ['city' => 'Multan',    'region' => 'Punjab',   'delivery_charge' => 200, 'estimated_days' => 3],
            ['city' => 'Faisalabad','region' => 'Punjab',   'delivery_charge' => 180, 'estimated_days' => 3],
            ['city' => 'Hyderabad', 'region' => 'Sindh',    'delivery_charge' => 180, 'estimated_days' => 3],
            ['city' => 'Other',     'region' => 'Pakistan', 'delivery_charge' => 350, 'estimated_days' => 7],
        ];

        foreach ($zones as $zone) {
            ShippingZone::firstOrCreate(['city' => $zone['city']], $zone);
        }
    }
}
