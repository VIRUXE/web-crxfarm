<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Chassis;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            ['title' => 'Del Sol Cluster Manual', 'chassis' => 'Del Sol', 'category' => PartCategory::Interior, 'price' => '$160', 'location' => 'Topeka, KS', 'description' => 'Original manual gauge cluster in good working condition.'],
            ['title' => 'CRX Sunroof Panels', 'chassis' => 'CRX', 'category' => PartCategory::ExteriorBody, 'price' => '$450', 'location' => 'Topeka, KS', 'description' => 'OEM steel sunroof panel assembly, clean seals.'],
            ['title' => '1992 Honda Civic VX Hatch', 'chassis' => 'EG', 'category' => PartCategory::Other, 'price' => null, 'location' => 'Topeka, KS', 'description' => 'EG hatch shell parts and trim.'],
            ['title' => 'OZ Racing Rims 115.x6 46', 'chassis' => 'CRX', 'also_fits' => ['EF', 'EG', 'Del Sol'], 'category' => PartCategory::WheelsTires, 'bolt_pattern' => '4x100', 'price' => '$550', 'location' => 'Topeka, KS', 'description' => 'Set of 4 vintage OZ Racing alloy rims, 4x100 bolt pattern.'],
            ['title' => 'B16A Power Steering Bracket', 'chassis' => 'EG', 'category' => PartCategory::EngineDrivetrain, 'price' => '$350', 'location' => 'Topeka, KS', 'description' => 'Factory aluminum power steering mounting bracket for B16A / B18.'],
            ['title' => 'Del Sol Aux Light Brackets', 'chassis' => 'Del Sol', 'category' => PartCategory::LightingElectrical, 'price' => '$190', 'location' => 'Topeka, KS', 'description' => 'Pair of OEM Del Sol auxiliary fog light mounting brackets with hardware.'],
            ['title' => 'K13 CRX Rear Garnish', 'chassis' => 'CRX', 'category' => PartCategory::ExteriorBody, 'price' => '$340', 'location' => 'Topeka, KS', 'description' => 'Center tail light garnish with crisp CRX logo.'],
            ['title' => 'D16Z6 VTEC Heads', 'chassis' => 'EG', 'category' => PartCategory::EngineDrivetrain, 'price' => '$750', 'location' => 'Topeka, KS', 'description' => 'Complete D16Z6 VTEC cylinder head with cam and rocker assembly.'],
            ['title' => 'CRX Si Front Seats (Pair)', 'chassis' => 'CRX', 'category' => PartCategory::Interior, 'price' => '$600', 'location' => 'Rossville, KS', 'description' => 'Original black cloth CRX Si bucket seats with red pin-striping.'],
            ['title' => 'EF Civic Amber Corner Lights', 'chassis' => 'EF', 'category' => PartCategory::LightingElectrical, 'price' => '$120', 'location' => 'Rossville, KS', 'description' => 'OEM Stanley amber front corner lamps, no broken tabs.'],
            ['title' => 'DC2 Integra Type R Strut Tower Bar', 'chassis' => 'DC2 Integra', 'category' => PartCategory::SuspensionBrakes, 'price' => '$220', 'location' => 'Rossville, KS', 'description' => 'Genuine OEM aluminum front upper strut brace.'],
            ['title' => 'B18C GSR Intake Manifold', 'chassis' => 'DC2 Integra', 'category' => PartCategory::EngineDrivetrain, 'price' => '$280', 'location' => 'Topeka, KS', 'description' => 'Dual-stage intake manifold assembly with secondary butterfly valves.'],
            ['title' => 'DA Integra Tail Light Set', 'chassis' => 'DA Integra', 'category' => PartCategory::LightingElectrical, 'price' => '$175', 'location' => 'Rossville, KS', 'description' => 'Complete left and right tail lamp assemblies with center garnish.'],
            ['title' => 'CR-V First Gen OEM Picnic Table', 'chassis' => 'CR-V', 'category' => PartCategory::Interior, 'price' => '$150', 'location' => 'Rossville, KS', 'description' => 'Original rear trunk cargo floor fold-out picnic table.'],
            ['title' => 'EK Civic Si Climate Control Bezel', 'chassis' => 'EK', 'category' => PartCategory::Interior, 'price' => '$140', 'location' => 'Topeka, KS', 'description' => 'Electronic climate control dash bezel and harness pig-tails.'],
            ['title' => 'S2000 AP1 Leather Steering Wheel', 'chassis' => 'S2000', 'category' => PartCategory::Interior, 'price' => '$320', 'location' => 'Rossville, KS', 'description' => 'Three-spoke perforated leather steering wheel with H-badge.'],
            ['title' => 'Prelude BB4 4WS Rear Steering Actuator', 'chassis' => 'Prelude', 'category' => PartCategory::SuspensionBrakes, 'price' => '$400', 'location' => 'Topeka, KS', 'description' => 'Tested electric 4-wheel-steering rear actuator motor and control module.'],
            ['title' => 'EF Civic Wagon Rear Mud Flaps', 'chassis' => 'Civic Wagon', 'category' => PartCategory::ExteriorBody, 'price' => '$130', 'location' => 'Rossville, KS', 'description' => 'Rare OEM molded rear mud guards with white 4WD lettering.'],
            ['title' => 'D16A6 Si MPFI Distributor', 'chassis' => 'CRX', 'category' => PartCategory::LightingElectrical, 'price' => '$110', 'location' => 'Rossville, KS', 'description' => 'OEM TD-02U multi-point fuel injection distributor with cap and rotor.'],
            ['title' => 'CRX HF Ultra-Lightweight Rear Drums', 'chassis' => 'CRX', 'category' => PartCategory::SuspensionBrakes, 'price' => '$90', 'location' => 'Topeka, KS', 'description' => 'Factory aluminum/finned lightweight rear brake drum set.'],
        ];

        foreach ($parts as $p) {
            $alsoFits = $p['also_fits'] ?? [];
            unset($p['also_fits']);

            $listing = Listing::firstOrCreate(
                ['title' => $p['title']],
                $p + ['type' => ListingType::Part, 'status' => 'available']
            );

            $chassisNames = array_unique(array_filter([$p['chassis'] ?? null, ...$alsoFits]));
            if ($chassisNames !== []) {
                $chassisIds = collect($chassisNames)
                    ->map(fn (string $name) => Chassis::firstOrCreate(['name' => $name])->id);
                $listing->compatibleChassis()->syncWithoutDetaching($chassisIds);
            }
        }

        $cars = [
            [
                'title' => '1988 Honda CRX Si - parts car',
                'type' => ListingType::Car,
                'chassis' => 'CRX',
                'price' => null,
                'location' => 'Rossville, KS',
                'status' => 'available',
                'missing_parts' => "Hood\nFront Bumper\nDriver Seat\nECU",
                'description' => '1988 CRX Si donor car sitting in the yard. Ask about anything specific and Jeremiah will confirm and quote shipping.',
            ],
            [
                'title' => '1991 Honda Civic EF Hatch - parts car',
                'type' => ListingType::Car,
                'chassis' => 'EF',
                'price' => null,
                'location' => 'Rossville, KS',
                'status' => 'available',
                'missing_parts' => "Engine (Long Block)\nTransmission\nWiring Harness\nHeadlights",
                'description' => '1991 EF Civic Hatchback DX donor car. Interior and body panels largely intact. Message for availability.',
            ],
            [
                'title' => '1995 Honda Del Sol Si - parts car',
                'type' => ListingType::Car,
                'chassis' => 'Del Sol',
                'price' => null,
                'location' => 'Topeka, KS',
                'status' => 'available',
                'missing_parts' => "Targa Top\nGauge Cluster\nCorner Lights\nCatalytic Converter",
                'description' => '1995 Del Sol Si donor chassis with D16Z6 engine still in bay. Many interior and exterior pieces available.',
            ],
            [
                'title' => '1998 Honda Civic EK Coupe - parts car',
                'type' => ListingType::Car,
                'chassis' => 'EK',
                'price' => null,
                'location' => 'Rossville, KS',
                'status' => 'available',
                'missing_parts' => "Front Bumper\nFender (Driver)\nFender (Passenger)\nAirbags",
                'description' => '1998 EK Civic EX donor coupe. D16Y8 engine, 5-speed manual, clean rear quarter panels and glass.',
            ],
        ];

        foreach ($cars as $car) {
            Listing::firstOrCreate(
                ['title' => $car['title']],
                $car
            );
        }
    }
}
