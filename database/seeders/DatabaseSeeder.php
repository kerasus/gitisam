<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Unit;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\Building;
use App\Models\Transaction;
use App\Models\InvoiceCategory;
use Illuminate\Database\Seeder;
use App\Models\InvoiceDistribution;
use App\Models\TransactionInvoiceDistribution;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks during seeding
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables to ensure clean state
        User::truncate();
        Unit::truncate();
        Image::truncate();
        Invoice::truncate();
        Building::truncate();
        Transaction::truncate();
        InvoiceCategory::truncate();
        InvoiceDistribution::truncate();
        TransactionInvoiceDistribution::truncate();

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            BuildingSeeder::class,
            UnitSeeder::class,
        ]);

        // Seed invoice types
        $invoiceCategoriesData = [
            [
                'name' => 'آب',
                'description' => '',
            ],
            [
                'name' => 'آسانسور',
                'description' => '',
            ],
            [
                'name' => 'نظافت',
                'description' => '',
            ],
            [
                'name' => 'شارژ ماهیانه',
                'description' => '',
            ],
        ];

        foreach ($invoiceCategoriesData as $invoiceCategoryData) {
            InvoiceCategory::factory()->create($invoiceCategoryData);
        }
    }
}
