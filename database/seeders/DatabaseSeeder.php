<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['user_login' => 'admin'],
            [
                'user_password' => Hash::make('Password1'),
                'user_compte_id' => 1,
                'user_mail' => 'admin@example.com',
            ]
        );
    }
}
