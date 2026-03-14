<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Create default admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@sidapil.local',
            'phone' => '08123456789',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Create sample users for each role
        User::create([
            'name' => 'Front Office User',
            'email' => 'fo@sidapil.local',
            'phone' => '08123456780',
            'password' => bcrypt('password'),
            'role' => User::ROLE_FRONT_OFFICE,
        ]);

        User::create([
            'name' => 'Operator User',
            'email' => 'operator@sidapil.local',
            'phone' => '08123456781',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        User::create([
            'name' => 'Customer Service User',
            'email' => 'cs@sidapil.local',
            'phone' => '08123456782',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER_SERVICE,
        ]);

        User::create([
            'name' => 'Petugas Loket',
            'email' => 'loket@sidapil.local',
            'phone' => '08123456783',
            'password' => bcrypt('password'),
            'role' => User::ROLE_LOKET,
        ]);
    }
}
