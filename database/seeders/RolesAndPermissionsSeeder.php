<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions by module
        $permissions = [
            'roles' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
            'users' => ['users.view', 'users.create', 'users.edit', 'users.delete', 'users.roles'],
            'settings' => ['settings.view', 'settings.edit'],
        ];

        foreach ($permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        // Create Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Give permissions to roles
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        $adminRole->syncPermissions(
            Permission::whereIn('name', [
                'roles.view',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.roles',
                'settings.view', 'settings.edit',
            ])->get()
        );

        $userRole->syncPermissions(
            Permission::whereIn('name', ['settings.view'])->get()
        );

        // Create 3 default users and assign roles
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles([$superAdminRole]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        $standardUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Standard User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $standardUser->syncRoles([$userRole]);
    }
}
