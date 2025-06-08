<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use App\Models\Building;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $building = Building::first();

        if (!$building) {
            throw new \Exception("The buildings table is empty. Please create at least one building first.");
        }

        // Retrieve all users from the database
        $users = User::all();

        if ($users->isEmpty()) {
            throw new \Exception("The users table is empty. Please seed users first.");
        }

        $units = [
            [
                'unit_number' => 1,
                'floor' => 1,
                'area' => 54.9,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای حسنی',
                'resident_phone' => '09191993023',
                'description' => '',
            ],
            [
                'unit_number' => 2,
                'floor' => 1,
                'area' => 70.7,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای اسماعیلی',
                'resident_phone' => '09358745928',
                'description' => '',
            ],
            [
                'unit_number' => 3,
                'floor' => 1,
                'area' => 69.49,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای کلهر',
                'resident_phone' => '09121506518',
                'description' => 'سرویسکار آسانسور',
            ],
            [
                'unit_number' => 4,
                'floor' => 1,
                'area' => 58.97,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'آقای رضائی',
                'resident_phone' => '09128110866',
                'description' => '',
            ],
            [
                'unit_number' => 5,
                'floor' => 2,
                'area' => 54.9,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'آقای پزشکیان',
                'resident_phone' => '09373526904',
                'description' => '',
            ],
            [
                'unit_number' => 6,
                'floor' => 2,
                'area' => 70.7,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای سلطانی',
                'resident_phone' => '0936636075',
                'description' => '',
            ],
            [
                'unit_number' => 7,
                'floor' => 2,
                'area' => 69.49,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 4,
                'resident_name' => 'آقای نیک پور',
                'resident_phone' => '09193636574',
                'description' => '',
            ],
            [
                'unit_number' => 8,
                'floor' => 2,
                'area' => 58.97,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای موسوی',
                'resident_phone' => '09108176794',
                'description' => '',
            ],
            [
                'unit_number' => 9,
                'floor' => 3,
                'area' => 54.9,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'خانم محمدی',
                'resident_phone' => '09127089567',
                'description' => '',
            ],
            [
                'unit_number' => 10,
                'floor' => 3,
                'area' => 70.7,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'خانم شاه محمدی',
                'resident_phone' => '09125769856',
                'description' => '',
            ],
            [
                'unit_number' => 11,
                'floor' => 3,
                'area' => 69.49,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'خانم شریفی',
                'resident_phone' => '09357099003',
                'description' => '',
            ],
            [
                'unit_number' => 12,
                'floor' => 3,
                'area' => 58.97,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای مولودی',
                'resident_phone' => '09125720879',
                'description' => '',
            ],
            [
                'unit_number' => 13,
                'floor' => 4,
                'area' => 54.9,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'آقای آرانی',
                'resident_phone' => '09137435580',
                'description' => '',
            ],
            [
                'unit_number' => 14,
                'floor' => 4,
                'area' => 70.7,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 4,
                'resident_name' => 'آقای شفائی',
                'resident_phone' => '09396368470',
                'description' => '',
            ],
            [
                'unit_number' => 15,
                'floor' => 4,
                'area' => 69.49,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 0,
                'resident_name' => 'آقای رضائی',
                'resident_phone' => '09192034942',
                'description' => 'در حال بازسازی',
            ],
            [
                'unit_number' => 16,
                'floor' => 4,
                'area' => 58.97,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 3,
                'resident_name' => 'آقای کاظمی',
                'resident_phone' => '09137903801',
                'description' => '',
            ],
            [
                'unit_number' => 17,
                'floor' => 5,
                'area' => 54.9,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 1,
                'resident_name' => 'آقای افخمی',
                'resident_phone' => '09125080534',
                'description' => '',
            ],
            [
                'unit_number' => 18,
                'floor' => 5,
                'area' => 70.7,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'پور محمد',
                'resident_phone' => '09359339381',
                'description' => '',
            ],
            [
                'unit_number' => 19,
                'floor' => 5,
                'area' => 69.49,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'آقای نعمتی',
                'resident_phone' => '09308297431',
                'description' => '',
            ],
            [
                'unit_number' => 20,
                'floor' => 5,
                'area' => 58.97,
                'parking_spaces' => 1,
                'number_of_rooms' => 2,
                'number_of_residents' => 2,
                'resident_name' => 'جنتیان',
                'resident_phone' => '09125769856',
                'description' => '',
            ],
        ];

        // Assign users to units
        foreach ($units as $index => $unitData) {

            // Extract resident information
            $residentName = $unitData['resident_name'];
            $residentPhone = $unitData['resident_phone'];

            // Split the resident's name into firstname and lastname
            $nameParts = explode(' ', $residentName, 2);
            $firstname = $nameParts[0];
            $lastname = $nameParts[1] ?? '';

            // Create or find the user
            $user = User::firstOrCreate(
                ['mobile' => $residentPhone],
                [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'username' => $residentPhone, // Use mobile number as username
                    'password' => bcrypt('123'), // Set a simple password for all users
                    'mobile' => $residentPhone,
                ]
            );

            // Prepare unit data
            unset($unitData['resident_name']);
            unset($unitData['resident_phone']);
            $unitData['building_id'] = $building->id;

            // Create the unit
            $unit = Unit::create($unitData);

            // Attach the user to the unit with the role 'resident'
            $unit->users()->attach($user->id, ['role' => 'resident']);
        }
    }
}
