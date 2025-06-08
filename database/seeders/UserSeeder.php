<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Retrieve roles from the database
        $managerRole = Role::where('name', 'Manager')->first();
        $accountantRole = Role::where('name', 'Accountant')->first();
        $residentRole = Role::where('name', 'Resident')->first();

        // Seed users
        $users = [
            [
                'firstname' => 'ali',
                'lastname' => 'esmaeili',
                'username' => 'admin',
                'password' => bcrypt('123456789'), // Hash the password
                'mobile' => '09999999999',
                'email' => 'test@example.com',
                'role' => 'Manager'
            ]
        ];

        foreach ($users as $userData) {
            // Remove the 'role' field from the user data
            $userRole = $userData['role'] ?? null;
            unset($userData['role']);

            // Create the user
            $user = User::factory()->create($userData);

            // Assign role to the user
            if (isset($userRole)) {
                if ($userRole === 'Manager') {
                    $user->assignRole($managerRole);
                } elseif ($userRole === 'Accountant') {
                    $user->assignRole($accountantRole);
                } elseif ($userRole === 'Resident') {
                    $user->assignRole($residentRole);
                }
            }
        }
    }
}
