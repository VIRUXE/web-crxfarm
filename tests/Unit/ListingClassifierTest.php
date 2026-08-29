<?php

namespace Tests\Unit;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Support\ListingClassifier;
use PHPUnit\Framework\TestCase;

class ListingClassifierTest extends TestCase
{
    public function test_honda_and_acura_listings_are_honda_related(): void
    {
        $this->assertTrue(ListingClassifier::isHondaRelated('CRX sunroof panels'));
        $this->assertTrue(ListingClassifier::isHondaRelated('B18C1 bare block gsr'));
        $this->assertTrue(ListingClassifier::isHondaRelated('94-01 Acura Integra LS transmission'));
        $this->assertTrue(ListingClassifier::isHondaRelated('D16Z6 VTEC motor'));
    }

    public function test_universal_parts_count_as_honda_related(): void
    {
        $this->assertTrue(ListingClassifier::isHondaRelated('OZ Racing Rims 115.x6 46'));
        $this->assertTrue(ListingClassifier::isHondaRelated('Sparco Racing Seats'));
    }

    public function test_other_brands_and_junk_are_not_honda_related(): void
    {
        $this->assertFalse(ListingClassifier::isHondaRelated('2002 Yamaha fx140'));
        $this->assertFalse(ListingClassifier::isHondaRelated('Microwave'));
        $this->assertFalse(ListingClassifier::isHondaRelated('Suburu SVX Leather Seats'));
        $this->assertFalse(ListingClassifier::isHondaRelated('NVIDIA GeForce RTX 3070'));
    }

    public function test_whole_vehicles_and_part_outs_classify_as_cars(): void
    {
        $this->assertSame(ListingType::Car, ListingClassifier::classify('1992 Honda civic VX Hatchback 2D')['type']);
        $this->assertSame(ListingType::Car, ListingClassifier::classify('1996 EK Hatch Part Out')['type']);
        $this->assertSame(ListingType::Part, ListingClassifier::classify('CRX sunroof panels')['type']);
    }

    public function test_categories_are_assigned_by_part_words(): void
    {
        $this->assertSame(PartCategory::EngineDrivetrain, ListingClassifier::classify('B16 Honda Transmission')['category']);
        $this->assertSame(PartCategory::ExteriorBody, ListingClassifier::classify('CRX Hatch Hood ef 88 to 91')['category']);
        $this->assertSame(PartCategory::Interior, ListingClassifier::classify('Del Sol Cluster Manual Honda')['category']);
        $this->assertSame(PartCategory::SuspensionBrakesWheels, ListingClassifier::classify('OZ Racing Rims 4x100')['category']);
        $this->assertSame(PartCategory::LightingElectrical, ListingClassifier::classify('Honda Civic Headlights 96,97,98')['category']);
        $this->assertSame(PartCategory::ExhaustIntake, ListingClassifier::classify('92-95 civic hatch exhaust invidia n1')['category']);
    }

    public function test_steering_wheel_is_interior_not_a_road_wheel(): void
    {
        $this->assertSame(PartCategory::Interior, ListingClassifier::classify('Nardi Steering wheel')['category']);
    }

    public function test_chassis_is_optional_and_can_be_multiple(): void
    {
        $this->assertSame([], ListingClassifier::classify('Sparco Racing Seats')['chassis']);
        $this->assertContains('CRX', ListingClassifier::classify('CRX & 3g Honda Civic SI Distributor')['chassis']);

        $delSol = ListingClassifier::classify('Del Sol Rear Garnish UKDM')['chassis'];
        $this->assertSame(['Del Sol'], $delSol);
    }

    public function test_cars_report_no_part_category(): void
    {
        $this->assertNull(ListingClassifier::classify('1993 Honda del sol VTEC Coupe 2D')['category']);
    }
}
