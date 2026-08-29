<?php

namespace Tests\Unit;

use App\Enums\PartCategory;
use PHPUnit\Framework\TestCase;

class PartCategoryEnumTest extends TestCase
{
    public function test_it_contains_expected_categories(): void
    {
        $expected = [
            'engine_drivetrain',
            'exterior_body',
            'interior',
            'lighting_electrical',
            'suspension_brakes',
            'wheels_tires',
            'exhaust_intake',
            'other',
        ];

        $this->assertSame($expected, PartCategory::values());
    }

    public function test_it_returns_clean_labels(): void
    {
        $this->assertSame('Engine & Drivetrain', PartCategory::EngineDrivetrain->label());
        $this->assertSame('Exterior & Body', PartCategory::ExteriorBody->label());
        $this->assertSame('Interior', PartCategory::Interior->label());
        $this->assertSame('Lighting & Electrical', PartCategory::LightingElectrical->label());
        $this->assertSame('Suspension & Brakes', PartCategory::SuspensionBrakes->label());
        $this->assertSame('Wheels & Tires', PartCategory::WheelsTires->label());
        $this->assertSame('Exhaust & Intake', PartCategory::ExhaustIntake->label());
        $this->assertSame('Other / Misc', PartCategory::Other->label());
    }

    public function test_options_returns_value_label_map(): void
    {
        $options = PartCategory::options();

        $this->assertArrayHasKey('engine_drivetrain', $options);
        $this->assertSame('Engine & Drivetrain', $options['engine_drivetrain']);
        $this->assertArrayHasKey('interior', $options);
        $this->assertSame('Interior', $options['interior']);
    }
}
