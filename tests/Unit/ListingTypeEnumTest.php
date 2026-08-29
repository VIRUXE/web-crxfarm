<?php

namespace Tests\Unit;

use App\Enums\ListingType;
use PHPUnit\Framework\TestCase;

class ListingTypeEnumTest extends TestCase
{
    public function test_it_contains_part_and_car_cases(): void
    {
        $this->assertSame(['part', 'car'], ListingType::values());
    }

    public function test_it_returns_human_friendly_labels(): void
    {
        $this->assertSame('Individual part', ListingType::Part->label());
        $this->assertSame('Donor car', ListingType::Car->label());
    }

    public function test_it_provides_boolean_helper_methods(): void
    {
        $this->assertTrue(ListingType::Car->isCar());
        $this->assertFalse(ListingType::Car->isPart());

        $this->assertTrue(ListingType::Part->isPart());
        $this->assertFalse(ListingType::Part->isCar());
    }

    public function test_options_returns_value_label_map(): void
    {
        $this->assertSame([
            'part' => 'Individual part',
            'car' => 'Donor car',
        ], ListingType::options());
    }
}
