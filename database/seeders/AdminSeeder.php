<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'malik.umerkhan97@gmail.com'],
            [
                'name' => 'Malik Umer Khan',
                'email' => 'malik.umerkhan97@gmail.com',
                'password' => Hash::make('malikawan97'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
