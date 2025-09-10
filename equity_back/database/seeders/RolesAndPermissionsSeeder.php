<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Permisos
        $perms = ['view-invoices', 'upload-invoices', 'manage-users'];
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([]);
    }
}
