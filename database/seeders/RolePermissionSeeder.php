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
        // Assign permissions to roles
        $resident = Role::findByName('Resident');
        $resident->givePermissionTo('view-invoices');

        $owner = Role::findByName('Owner');
        $owner->givePermissionTo(['view-invoices', 'manage-invoices']);

        $accountant = Role::findByName('Accountant');
        $accountant->givePermissionTo([
            'view-invoices',
            'manage-invoices',
            'view-transactions',
            'manage-transactions'
        ]);

        $manager = Role::findByName('Manager');
        $manager->givePermissionTo(Permission::all());
    }
}
