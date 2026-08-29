<?php

namespace Tests\Unit;

use App\Models\Listing;
use PHPUnit\Framework\TestCase;

class ListingMissingPartsTest extends TestCase
{
    public function test_it_returns_empty_array_when_null_or_blank(): void
    {
        $listing = new Listing(['missing_parts' => null]);
        $this->assertSame([], $listing->missingPartsList());

        $listing = new Listing(['missing_parts' => '   ']);
        $this->assertSame([], $listing->missingPartsList());
    }

    public function test_it_parses_newline_separated_items(): void
    {
        $listing = new Listing([
            'missing_parts' => "Hood\nFront bumper\nDriver seat\nECU",
        ]);

        $this->assertSame([
            'Hood',
            'Front bumper',
            'Driver seat',
            'ECU',
        ], $listing->missingPartsList());
    }

    public function test_it_strips_bullets_dashes_and_numbers(): void
    {
        $listing = new Listing([
            'missing_parts' => "- Hood\n* Front bumper\n• Driver seat\n1. ECU",
        ]);

        $this->assertSame([
            'Hood',
            'Front bumper',
            'Driver seat',
            'ECU',
        ], $listing->missingPartsList());
    }

    public function test_it_parses_comma_and_semicolon_separated_items(): void
    {
        $listing = new Listing([
            'missing_parts' => 'Hood, Front bumper, Driver seat; ECU',
        ]);

        $this->assertSame([
            'Hood',
            'Front bumper',
            'Driver seat',
            'ECU',
        ], $listing->missingPartsList());
    }

    public function test_it_ignores_empty_lines(): void
    {
        $listing = new Listing([
            'missing_parts' => "\n\nHood\n\n\nFront bumper\n\n",
        ]);

        $this->assertSame([
            'Hood',
            'Front bumper',
        ], $listing->missingPartsList());
    }
}
