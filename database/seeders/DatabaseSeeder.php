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
        User::firstOrCreate(
            ['email' => 'admin@sidapil.local'],
            [
                'name' => 'Administrator',
                'phone' => '08123456789',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        // Create sample users for each role
        User::firstOrCreate(
            ['email' => 'fo@sidapil.local'],
            [
                'name' => 'Front Office User',
                'phone' => '08123456780',
                'password' => bcrypt('password'),
                'role' => User::ROLE_FRONT_OFFICE,
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@sidapil.local'],
            [
                'name' => 'Operator User',
                'phone' => '08123456781',
                'password' => bcrypt('password'),
                'role' => User::ROLE_OPERATOR,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cs@sidapil.local'],
            [
                'name' => 'Customer Service User',
                'phone' => '08123456782',
                'password' => bcrypt('password'),
                'role' => User::ROLE_CUSTOMER_SERVICE,
            ]
        );

        User::firstOrCreate(
            ['email' => 'loket@sidapil.local'],
            [
                'name' => 'Petugas Loket',
                'phone' => '08123456783',
                'password' => bcrypt('password'),
                'role' => User::ROLE_LOKET,
            ]
        );
    }
}
