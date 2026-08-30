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

    public function test_tags_returns_curated_tags_for_each_category(): void
    {
        $interiorTags = PartCategory::Interior->tags();
        $this->assertArrayHasKey('seats', $interiorTags);
        $this->assertSame('Seats & Rails', $interiorTags['seats']['label']);
        $this->assertContains('seat', $interiorTags['seats']['keywords']);

        $engineTags = PartCategory::EngineDrivetrain->tags();
        $this->assertArrayHasKey('transmissions', $engineTags);
        $this->assertArrayHasKey('b_series', $engineTags);
        $this->assertSame('B-Series', $engineTags['b_series']['label']);

        $allCategories = PartCategory::cases();
        foreach ($allCategories as $category) {
            $tags = $category->tags();
            $this->assertNotEmpty($tags, "Category {$category->value} should have curated tags.");
            foreach ($tags as $tagKey => $tagData) {
                $this->assertArrayHasKey('label', $tagData);
                $this->assertArrayHasKey('keywords', $tagData);
                $this->assertNotEmpty($tagData['keywords']);
            }
        }
    }

    public function test_tags_for_helper_returns_expected_tags(): void
    {
        $tagsFromEnum = PartCategory::tagsFor(PartCategory::Interior);
        $tagsFromString = PartCategory::tagsFor('interior');

        $this->assertSame($tagsFromEnum, $tagsFromString);
        $this->assertArrayHasKey('seats', $tagsFromString);
        $this->assertEmpty(PartCategory::tagsFor(null));
        $this->assertEmpty(PartCategory::tagsFor('invalid_category'));
    }
}
