<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'view-invoices']);
        Permission::create(['name' => 'manage-invoices']);
        Permission::create(['name' => 'view-transactions']);
        Permission::create(['name' => 'manage-transactions']);
        Permission::create(['name' => 'manage-building']);
    }
}
