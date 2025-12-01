<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SupportAccountantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Support user
        User::firstOrCreate(
            ['email' => 'support@example.com'],
            [
                'name' => 'Support User',
                'password' => Hash::make('123456'),
                'role' => 'support',
                'country_code' => 'AE',
            ]
        );

        // Create Accountant user
        User::firstOrCreate(
            ['email' => 'accountant@example.com'],
            [
                'name' => 'Accountant User',
                'password' => Hash::make('123456'),
                'role' => 'accountant',
                'country_code' => 'AE',
            ]
        );
    }
}



