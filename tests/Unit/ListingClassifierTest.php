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
        $this->assertSame(PartCategory::WheelsTires, ListingClassifier::classify('OZ Racing Rims 4x100')['category']);
        $this->assertSame(PartCategory::SuspensionBrakes, ListingClassifier::classify('DC2 Integra Strut Tower Bar')['category']);
        $this->assertSame(PartCategory::LightingElectrical, ListingClassifier::classify('Honda Civic Headlights 96,97,98')['category']);
        $this->assertSame(PartCategory::ExhaustIntake, ListingClassifier::classify('92-95 civic hatch exhaust invidia n1')['category']);
    }

    public function test_steering_wheel_is_interior_not_a_road_wheel(): void
    {
        $this->assertSame(PartCategory::Interior, ListingClassifier::classify('Nardi Steering wheel')['category']);
    }

    public function test_detects_bolt_patterns(): void
    {
        $this->assertSame('4x100', ListingClassifier::classify('OZ Racing Rims 4x100')['bolt_pattern']);
        $this->assertSame('4x114.3', ListingClassifier::classify('Accord 4x114 aftermarket wheels')['bolt_pattern']);
        $this->assertSame('5x114.3', ListingClassifier::classify('RSX Type S rims 5x114.3')['bolt_pattern']);
        $this->assertSame('5x120', ListingClassifier::classify('Civic Type R FK8 5x120 wheels')['bolt_pattern']);
        $this->assertSame('4x100, 4x114.3', ListingClassifier::classify('Enkie 17 rims', "4x100\n4x114")['bolt_pattern']);
        $this->assertNull(ListingClassifier::classify('CRX Sunroof Panels')['bolt_pattern']);
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

    public function test_detects_and_cleans_placeholder_prices(): void
    {
        $this->assertTrue(ListingClassifier::isPlaceholderPrice('$123'));
        $this->assertTrue(ListingClassifier::isPlaceholderPrice('$1,234'));
        $this->assertTrue(ListingClassifier::isPlaceholderPrice('123'));
        $this->assertTrue(ListingClassifier::isPlaceholderPrice('123456'));
        $this->assertTrue(ListingClassifier::isPlaceholderPrice('$123,456'));

        $this->assertFalse(ListingClassifier::isPlaceholderPrice('$450'));
        $this->assertFalse(ListingClassifier::isPlaceholderPrice('$120'));
        $this->assertFalse(ListingClassifier::isPlaceholderPrice('$1,200'));
        $this->assertFalse(ListingClassifier::isPlaceholderPrice(null));

        $this->assertNull(ListingClassifier::cleanPrice('$123'));
        $this->assertNull(ListingClassifier::cleanPrice('$1,234'));
        $this->assertNull(ListingClassifier::cleanPrice('123'));
        $this->assertSame('$450', ListingClassifier::cleanPrice('$450'));
        $this->assertSame('$1,200', ListingClassifier::cleanPrice('$1,200'));
    }

    public function test_part_out_listings_classify_as_cars_with_null_price(): void
    {
        $delSol = ListingClassifier::classify('1995 Honda del sol', 'Full professional part out. Skipped title. Crx farm', '$123');
        $this->assertSame(ListingType::Car, $delSol['type']);
        $this->assertContains('Del Sol', $delSol['chassis']);
        $this->assertNull($delSol['category']);
        $this->assertNull($delSol['clean_price']);

        $integra = ListingClassifier::classify('1991 Acura Integra DA', '91 DA Integra full Part out. Clean title. Crx farm', '$123');
        $this->assertSame(ListingType::Car, $integra['type']);
        $this->assertContains('DA Integra', $integra['chassis']);
        $this->assertNull($integra['category']);
        $this->assertNull($integra['clean_price']);
    }

    public function test_seller_signature_crx_farm_does_not_corrupt_chassis_detection(): void
    {
        $integra = ListingClassifier::classify('1997 Acura integra GS Sport Coupe 2D', 'Full professional part out. Crx farm', '$123');
        $this->assertNotContains('CRX', $integra['chassis']);
        $this->assertContains('DC2 Integra', $integra['chassis']);

        $crv = ListingClassifier::classify('2003 Honda cr-v LX Sport Utility 4D', 'Clean title, needs o2 sensors. Crx farm', '$123');
        $this->assertNotContains('CRX', $crv['chassis']);
        $this->assertContains('CR-V', $crv['chassis']);
    }

    public function test_multi_part_listings_remain_parts_with_cleaned_price(): void
    {
        $bumpers = ListingClassifier::classify('CRX Bumper supports oem', '420 hf bumper support rear 280 front', '$123');
        $this->assertSame(ListingType::Part, $bumpers['type']);
        $this->assertSame(PartCategory::ExteriorBody, $bumpers['category']);
        $this->assertNull($bumpers['clean_price']);
    }
}
