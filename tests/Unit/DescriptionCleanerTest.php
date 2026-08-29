<?php

namespace Tests\Unit;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Listing;
use App\Support\DescriptionCleaner;
use Tests\TestCase;

class DescriptionCleanerTest extends TestCase
{
    public function test_cleans_hidden_information_and_pm_noise(): void
    {
        $raw = "Skunk2 Racing [hidden information] Pro Series Stage 1 Camshaft Set NEW\n[hidden information]\nHONDA H22A 2.2L DOHC VTEC\npm for faster response\ncrx farm";

        $cleaned = DescriptionCleaner::clean($raw);

        $this->assertStringNotContainsString('[hidden information]', $cleaned);
        $this->assertStringNotContainsString('pm for faster response', $cleaned);
        $this->assertStringNotContainsString('crx farm', $cleaned);
        $this->assertStringContainsString('Skunk2 Racing Pro Series Stage 1 Camshaft Set NEW', $cleaned);
        $this->assertStringContainsString('HONDA H22A 2.2L DOHC VTEC', $cleaned);
    }

    public function test_detects_brand_names(): void
    {
        $this->assertSame('Skunk2', DescriptionCleaner::brandName('Skunk2 Pro Series Cam Gears'));
        $this->assertSame('Acura', DescriptionCleaner::brandName('1994 Acura Integra GSR'));
        $this->assertSame('Mugen', DescriptionCleaner::brandName('Mugen MR5 Wheels 4x100'));
        $this->assertSame('Honda', DescriptionCleaner::brandName('Civic EF Front Bumper'));
    }

    public function test_seo_meta_description_generation(): void
    {
        $part = new Listing([
            'type' => ListingType::Part,
            'title' => 'Honda Civic EF Hatch Hood',
            'category' => PartCategory::ExteriorBody,
            'price' => '$250',
            'description' => 'Good condition hood. Shipping available.',
        ]);

        $meta = DescriptionCleaner::seoMetaDescription($part);

        $this->assertLessThanOrEqual(160, mb_strlen($meta));
        $this->assertStringContainsString('Civic EF Hatch Hood', $meta);
        $this->assertStringContainsString('$250', $meta);
    }

    public function test_schema_json_ld_structure(): void
    {
        $part = new Listing([
            'id' => 101,
            'type' => ListingType::Part,
            'title' => 'Skunk2 Cam Gears',
            'category' => PartCategory::EngineDrivetrain,
            'price' => '$260',
            'status' => 'available',
            'description' => 'Brand new cam gears.',
        ]);

        $schema = DescriptionCleaner::schemaJsonLd($part);

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertIsArray($schema['@graph']);
        $this->assertSame('Product', $schema['@graph'][0]['@type']);
        $this->assertSame('Skunk2', $schema['@graph'][0]['brand']['name']);
        $this->assertSame(260.0, $schema['@graph'][0]['offers']['price']);
        $this->assertSame('https://schema.org/UsedCondition', $schema['@graph'][0]['offers']['itemCondition']);
    }
}
