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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Agent management
            'view agents',
            'create agents',
            'edit agents',
            'delete agents',

            // Admin specific
            'manage roles',
            'manage permissions',
            'view system settings',
            'manage system settings',

            // Agent specific
            'view own profile',
            'edit own profile',
            'view assigned tasks',
            'update task status',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $agentRole = Role::create(['name' => 'agent']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Agent gets limited permissions
        $agentRole->givePermissionTo([
            'view own profile',
            'edit own profile',
            'view assigned tasks',
            'update task status',
        ]);
    }
}
