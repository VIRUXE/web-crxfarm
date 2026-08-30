<?php

namespace Tests\Unit;

use App\Enums\PartCategory;
use PHPUnit\Framework\TestCase;

class ListingCategoryTagTest extends TestCase
{
    public function test_tags_are_defined_across_all_part_categories(): void
    {
        foreach (PartCategory::cases() as $category) {
            $tags = $category->tags();
            $this->assertNotEmpty($tags);

            foreach ($tags as $key => $tag) {
                $this->assertIsString($key);
                $this->assertArrayHasKey('label', $tag);
                $this->assertArrayHasKey('keywords', $tag);
                $this->assertNotEmpty($tag['keywords']);
            }
        }
    }

    public function test_tag_keywords_match_expected_inventory(): void
    {
        $interiorTags = PartCategory::tagsFor('interior');
        $this->assertArrayHasKey('seats', $interiorTags);
        $this->assertArrayHasKey('gauge_clusters', $interiorTags);
        $this->assertArrayHasKey('steering_wheels', $interiorTags);

        $engineTags = PartCategory::tagsFor('engine_drivetrain');
        $this->assertArrayHasKey('transmissions', $engineTags);
        $this->assertArrayHasKey('b_series', $engineTags);
        $this->assertArrayHasKey('d_series', $engineTags);
        $this->assertArrayHasKey('k_series', $engineTags);
    }
}
