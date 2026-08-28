<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            ['title' => 'Del Sol Cluster Manual', 'chassis' => 'Del Sol', 'price' => '$160', 'location' => 'Topeka, KS'],
            ['title' => 'CRX Sunroof Panels', 'chassis' => 'CRX', 'price' => '$450', 'location' => 'Topeka, KS'],
            ['title' => '1992 Honda Civic VX Hatch', 'chassis' => 'EG Hatch', 'price' => '$123', 'location' => 'Topeka, KS'],
            ['title' => 'OZ Racing Rims 115.x6 46', 'chassis' => 'Wheels', 'price' => '$550', 'location' => 'Topeka, KS'],
            ['title' => 'B16A Power Steering Bracket', 'chassis' => 'EG', 'price' => '$350', 'location' => 'Topeka, KS'],
            ['title' => 'Del Sol Aux Light Brackets', 'chassis' => 'Del Sol', 'price' => '$190', 'location' => 'Topeka, KS'],
            ['title' => 'K13 CRX Rear Garnish', 'chassis' => 'Del Sol', 'price' => '$340', 'location' => 'Topeka, KS'],
            ['title' => 'D16Z6 VTEC Heads', 'chassis' => 'EG/EK', 'price' => '$750', 'location' => 'Topeka, KS'],
        ];

        foreach ($parts as $p) {
            Listing::firstOrCreate(
                ['title' => $p['title']],
                $p + ['type' => 'part', 'status' => 'available']
            );
        }

        Listing::firstOrCreate(
            ['title' => '1988 Honda CRX Si - parts car'],
            [
                'type' => 'car',
                'chassis' => 'CRX',
                'price' => null,
                'location' => 'Rossville, KS',
                'status' => 'available',
                'missing_parts' => 'Hood, front bumper, driver seat, and ECU already pulled. Everything else is likely still on the car.',
                'description' => '1988 CRX Si donor car sitting in the yard. Ask about anything specific and Jeremiah will confirm and quote shipping.',
            ]
        );
    }
}
