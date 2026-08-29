<?php

namespace App\Support;

use App\Models\Listing;
use Illuminate\Support\Str;

class DescriptionCleaner
{
    /**
     * Patterns to remove from listing descriptions (scrape artifacts and contact noise).
     */
    private const REMOVAL_PATTERNS = [
        // Scraped Facebook artifacts
        '/\[hidden information\]/i',

        // Direct message / PM noise
        '/^\s*(?:pm|dm|message)(?:\s+me)?\s+for\s+(?:faster|fast|quick)\s+response[^\r\n]*/mi',
        '/^\s*(?:pm|dm|message)(?:\s+me)?\s+for\s+(?:price|pricing|details|availability|pics|photos|inquiries)[^\r\n]*/mi',
        '/^\s*(?:pm|dm|message)(?:\s+me)?\s+if\s+interested[^\r\n]*/mi',
        '/^\s*(?:pm|dm)\s+(?:me\b)?\s*$/mi',

        // Standalone seller signature lines
        '/^\s*crx\s*farm\s*$/mi',
    ];

    /**
     * Known automotive marques and aftermarket brands to recognize in titles.
     */
    private const KNOWN_BRANDS = [
        'Acura',
        'Honda',
        'Skunk2',
        'Mugen',
        'Spoon',
        'Hasport',
        'Nardi',
        'Enkei',
        'Konig',
        'Rota',
        'Whelen',
        'Invidia',
        'Blackworks',
        'Sparco',
        'LeBra',
        'Gathers',
        'Bride',
        'Top Fuel',
        'Charge Speed',
        'Buddy Club',
        'Stanley',
        'BBS',
        'SSR',
        'OZ',
        'HKS',
        'NRG',
        'AEM',
        'DC Sports',
        'Blox',
        'Moroso',
        'Walbro',
        'Edelbrock',
    ];

    /**
     * Cleans raw scraped listing descriptions:
     * - Removes Facebook placeholders and contact noise
     * - Preserves actual part details, specs, pricing lines, and notes
     * - Cleans up spacing and blank lines
     */
    public static function clean(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $text = $description;

        foreach (self::REMOVAL_PATTERNS as $pattern) {
            $text = (string) preg_replace($pattern, '', $text);
        }

        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Split lines, collapse consecutive horizontal spaces in each line, and trim
        $rawLines = explode("\n", $text);
        $filteredLines = [];
        $prevEmpty = false;

        foreach ($rawLines as $line) {
            $trimmed = trim((string) preg_replace('/[^\S\r\n]+/', ' ', $line));
            if ($trimmed === '') {
                if (! $prevEmpty && count($filteredLines) > 0) {
                    $filteredLines[] = '';
                    $prevEmpty = true;
                }
            } else {
                $filteredLines[] = $trimmed;
                $prevEmpty = false;
            }
        }

        $result = trim(implode("\n", $filteredLines));

        return $result !== '' ? $result : null;
    }

    /**
     * Detects the brand from the listing title or defaults to Honda.
     */
    public static function brandName(string $title): string
    {
        foreach (self::KNOWN_BRANDS as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/i', $title)) {
                return $brand;
            }
        }

        return 'Honda';
    }

    /**
     * Generates a concise, keyword-rich SEO meta description (<160 chars)
     * optimized for search engine click-through rates.
     */
    public static function seoMetaDescription(Listing $listing): string
    {
        $brand = self::brandName($listing->title);
        $chassis = $listing->chassisLabel();
        $price = $listing->price ? ' for '.$listing->price : '';

        if ($listing->isCar()) {
            $desc = "{$listing->title} donor car{$price} in Rossville, KS. Part of 150+ Hondas parted out by CRX Farm. Nationwide & worldwide shipping available.";
        } else {
            $fits = $chassis !== '' ? " fits {$chassis}" : '';
            $desc = "Used {$brand} {$listing->title}{$fits}{$price}. Inspected OEM/aftermarket Honda parts from CRX Farm in Rossville, Kansas. We ship worldwide.";
        }

        // If a clean original description provides valuable extra context, incorporate a snippet if brief
        $cleaned = self::clean($listing->description);
        if ($cleaned && mb_strlen($cleaned) > 25 && ! str_contains($cleaned, 'http')) {
            $firstSentence = preg_split('/(?<=[.?!])\s+/', $cleaned, 2)[0] ?? $cleaned;
            $squashed = Str::squish($firstSentence);
            if (mb_strlen($squashed) >= 20 && mb_strlen($squashed) <= 90) {
                $candidate = "{$listing->title}{$price}. {$squashed} Ships from CRX Farm, Rossville, KS.";
                if (mb_strlen($candidate) <= 160) {
                    return $candidate;
                }
            }
        }

        return Str::limit(Str::squish($desc), 155, '');
    }

    /**
     * Generates Schema.org JSON-LD structured data for the listing.
     *
     * @return array<string, mixed>
     */
    public static function schemaJsonLd(Listing $listing): array
    {
        $brand = self::brandName($listing->title);
        $images = $listing->images->map(fn ($img) => $img->url)->values()->all();
        $cleanDesc = self::clean($listing->description) ?? self::seoMetaDescription($listing);

        // Numeric price parse if available
        $priceDigits = preg_replace('/[^\d.]/', '', (string) $listing->price);
        $numericPrice = is_numeric($priceDigits) && (float) $priceDigits > 0 ? (float) $priceDigits : null;

        $offer = [
            '@type' => 'Offer',
            'url' => $listing->url(),
            'priceCurrency' => 'USD',
            'availability' => $listing->status === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
            'seller' => [
                '@type' => 'AutoPartsStore',
                'name' => 'CRX Farm',
                'url' => url('/'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Rossville',
                    'addressRegion' => 'KS',
                    'addressCountry' => 'US',
                ],
            ],
        ];

        if ($numericPrice !== null) {
            $offer['price'] = $numericPrice;
        }

        $mainEntity = [
            '@type' => $listing->isCar() ? 'Vehicle' : 'Product',
            '@id' => $listing->url().'#item',
            'name' => $listing->title,
            'description' => Str::limit(Str::squish($cleanDesc), 500),
            'brand' => [
                '@type' => 'Brand',
                'name' => $brand,
            ],
            'offers' => $offer,
        ];

        if (! empty($images)) {
            $mainEntity['image'] = $images;
        }

        if ($listing->category) {
            $mainEntity['category'] = $listing->category->label();
        }

        $breadcrumbs = [
            '@type' => 'BreadcrumbList',
            '@id' => $listing->url().'#breadcrumbs',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $listing->isCar() ? 'Donor Cars' : 'Honda Parts',
                    'item' => route('catalog.index', ['type' => $listing->type->value]),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $listing->title,
                    'item' => $listing->url(),
                ],
            ],
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $mainEntity,
                $breadcrumbs,
            ],
        ];
    }
}
