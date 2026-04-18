<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'malik.umerkhan97@gmail.com'],
            [
                'name' => 'Malik Umer Khan',
                'password' => Hash::make('malikawan97'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
    }
}
