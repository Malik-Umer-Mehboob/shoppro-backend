<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::firstOrCreate(
            ['email' => 'testseller@yahoo.com'],
            [
                'name' => 'Test Seller',
                'password' => Hash::make('Seller@123'),
            ]
        );
        $seller->assignRole('seller');
    }
}
