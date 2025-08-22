<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissions
        Permission::firstOrCreate(['name' => 'access_filament']);
        Permission::firstOrCreate(['name' => 'manage_shops']);
        Permission::firstOrCreate(['name' => 'manage_products']);
        Permission::firstOrCreate(['name' => 'make_sale']);
        Permission::firstOrCreate(['name' => 'manage_roles']);
        Permission::firstOrCreate(['name' => 'manage_permissions']);

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $shopOwner  = Role::firstOrCreate(['name' => 'shop_owner']);
        $staff      = Role::firstOrCreate(['name' => 'staff']);

        // Role Permissions
        $superAdmin->givePermissionTo(Permission::all());
        $shopOwner->givePermissionTo(['access_filament', 'manage_products', 'make_sale']);
        $staff->givePermissionTo(['access_filament', 'make_sale']);
    }
}
