<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::firstOrCreate(
            ['email' => 'testcustomer@gmail.com'],
            [
                'name' => 'Test Customer',
                'password' => Hash::make('Customer@123'),
            ]
        );
        $customer->assignRole('customer');
    }
}
