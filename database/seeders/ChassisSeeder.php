<?php

namespace Database\Seeders;

use App\Models\Chassis;
use Illuminate\Database\Seeder;

class ChassisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'CRX',
            'EF',
            'EG',
            'EK',
            'Del Sol',
            'DA Integra',
            'DC2 Integra',
            'Accord',
            'CR-V',
            'Prelude',
            'S2000',
            'Civic Wagon',
            'Fit',
        ];

        foreach ($names as $name) {
            Chassis::firstOrCreate(['name' => $name]);
        }
    }
}
