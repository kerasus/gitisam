<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Resident']); // ساکن واحد
        Role::create(['name' => 'Owner']);   // مالک واحد
        Role::create(['name' => 'Accountant']); // حسابدار ساختمان
        Role::create(['name' => 'Manager']); // مدیر ساختمان
    }
}
