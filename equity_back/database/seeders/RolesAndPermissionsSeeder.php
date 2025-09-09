<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos
        Permission::firstOrCreate(['name' => 'view-invoices']);
        Permission::firstOrCreate(['name' => 'upload-invoices']);

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(['view-invoices', 'upload-invoices']);
    }
}
