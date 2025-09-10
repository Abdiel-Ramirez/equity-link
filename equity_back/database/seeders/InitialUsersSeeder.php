<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InitialUsersSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'), 
                'role' => $adminRole,
            ],
            [
                'name' => 'CEO',
                'email' => 'ceo@example.com',
                'password' => Hash::make('password'),
                'role' => $adminRole,
            ],
            [
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'password' => Hash::make('password'),
                'role' => $userRole,
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'password' => Hash::make('password'),
                'role' => $userRole,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']], 
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                ]
            );

            // Asignar rol
            $user->syncRoles([$data['role']->name]);
        }
    }
}
