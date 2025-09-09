<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos si no existen
        $viewInvoices = Permission::firstOrCreate(['name' => 'view-invoices']);
        $uploadInvoices = Permission::firstOrCreate(['name' => 'upload-invoices']);

        // Crear rol admin y asignarle permisos
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([$viewInvoices, $uploadInvoices]);

        // Crear rol user y asignarle permisos
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([$viewInvoices]);
    }
}
