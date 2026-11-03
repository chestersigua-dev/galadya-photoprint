<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Modules & Permissions
        $modules = [
            'dashboard' => [
                ['name' => 'View Dashboard', 'slug' => 'dashboard.view'],
            ],
            'clients' => [
                ['name' => 'View Clients', 'slug' => 'clients.view'],
                ['name' => 'Create Clients', 'slug' => 'clients.create'],
                ['name' => 'Edit Clients', 'slug' => 'clients.edit'],
                ['name' => 'Delete Clients', 'slug' => 'clients.delete'],
            ],
            'packages' => [
                ['name' => 'View Packages', 'slug' => 'packages.view'],
                ['name' => 'Manage Packages & Add-ons', 'slug' => 'packages.manage'],
            ],
            'import_print' => [
                ['name' => 'Start Import & Print Workflow', 'slug' => 'workflow.start'],
                ['name' => 'Process RAW & Standard Images', 'slug' => 'workflow.process'],
                ['name' => 'Generate 4R Print Templates', 'slug' => 'workflow.print'],
            ],
            'transactions' => [
                ['name' => 'View Transactions', 'slug' => 'transactions.view'],
                ['name' => 'Create Transactions', 'slug' => 'transactions.create'],
                ['name' => 'Print Receipts & Reprints', 'slug' => 'transactions.receipt'],
            ],
            'users_roles' => [
                ['name' => 'View Users & Roles', 'slug' => 'users.view'],
                ['name' => 'Manage Users & Permissions', 'slug' => 'users.manage'],
            ],
            'reports' => [
                ['name' => 'View Revenue & Sales Reports', 'slug' => 'reports.view'],
            ],
        ];

        $permissionModels = [];
        foreach ($modules as $moduleName => $permissions) {
            foreach ($permissions as $perm) {
                $permissionModels[$perm['slug']] = Permission::firstOrCreate(
                    ['slug' => $perm['slug']],
                    [
                        'name' => $perm['name'],
                        'module' => $moduleName,
                    ]
                );
            }
        }

        // 2. Define Roles
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Complete control over the studio system, users, settings, and workflows.',
            ]
        );

        $storeManager = Role::firstOrCreate(
            ['slug' => 'store_manager'],
            [
                'name' => 'Store Manager',
                'description' => 'Manages daily operations, studio packages, clients, revenue, and staff.',
            ]
        );

        $photographer = Role::firstOrCreate(
            ['slug' => 'photographer'],
            [
                'name' => 'Photographer / Operator',
                'description' => 'Operates the self-shoot booth, imports RAW files, selects photos, and triggers 4R prints.',
            ]
        );

        // 3. Assign Permissions
        // Super Admin gets all permissions
        $superAdmin->permissions()->sync(collect($permissionModels)->pluck('id'));

        // Store Manager permissions
        $managerPerms = collect($permissionModels)->filter(function ($perm) {
            return !in_array($perm->slug, ['users.manage'], true);
        })->pluck('id');
        $storeManager->permissions()->sync($managerPerms);

        // Photographer permissions: dashboard, clients (view/create), import_print, transactions (view/create/receipt)
        $photographerPerms = collect($permissionModels)->filter(function ($perm) {
            return in_array($perm->module, ['dashboard', 'clients', 'import_print', 'transactions'], true)
                && !in_array($perm->slug, ['clients.delete'], true);
        })->pluck('id');
        $photographer->permissions()->sync($photographerPerms);
    }
}

