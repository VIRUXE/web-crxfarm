<?php

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Chassis;
use App\Models\Listing;
use App\Support\OgImageGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $selectedCategory = $request->query('category') ? PartCategory::tryFrom((string) $request->query('category')) : null;
        $selectedType = $request->query('type') ? ListingType::tryFrom((string) $request->query('type')) : null;
        $selectedTag = $request->query('tag');

        $listings = Listing::query()
            ->where('status', 'available')
            ->search($request->query('q'))
            ->chassis($request->query('chassis'))
            ->type($selectedType)
            ->category($selectedCategory)
            ->categoryTag($selectedCategory, $selectedTag)
            ->when($selectedType === ListingType::Car, fn ($q) => $q->carTag($selectedTag))
            ->boltPattern($request->query('bolt_pattern'))
            ->with(['images' => fn ($q) => $q->limit(1), 'thumbnailImage', 'compatibleChassis'])
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $carChassis = Listing::query()
            ->whereNotNull('chassis')
            ->where('chassis', '!=', '')
            ->distinct()
            ->pluck('chassis');

        $chassisOptions = $carChassis
            ->merge(Chassis::query()->pluck('name'))
            ->unique()
            ->sort()
            ->values();

        $categories = PartCategory::cases();
        $categoryTags = $selectedCategory
            ? $selectedCategory->tags()
            : ($selectedType === ListingType::Car ? Listing::carTags() : []);

        $availablePatterns = Listing::query()
            ->whereNotNull('bolt_pattern')
            ->where('bolt_pattern', '!=', '')
            ->distinct()
            ->pluck('bolt_pattern')
            ->flatMap(fn ($bp) => array_map('trim', explode(',', $bp)))
            ->unique()
            ->filter()
            ->values();

        $boltPatternOptions = collect(Listing::standardBoltPatterns())
            ->merge($availablePatterns)
            ->unique()
            ->values();

        $view = $request->boolean('partial') || $request->header('HX-Request')
            ? 'catalog.partials.grid'
            : 'catalog.index';

        return view($view, compact('listings', 'chassisOptions', 'categories', 'categoryTags', 'selectedCategory', 'selectedType', 'boltPatternOptions'));
    }

    public function show(Listing $listing, ?string $slug = null): View|RedirectResponse
    {
        if ($slug !== null && $slug !== $listing->slug()) {
            return redirect()->route('catalog.show', [$listing, $listing->slug()], 301);
        }

        $listing->load(['images', 'compatibleChassis', 'thumbnailImage']);

        $featuredImage = $listing->featuredImage();

        return view('catalog.show', [
            'listing' => $listing,
            'title' => $listing->title.' | CRX Farm',
            'metaDescription' => $listing->seoMetaDescription(),
            'canonicalUrl' => $listing->url(),
            'ogImage' => $featuredImage?->og_url,
            // og_path is only set once an image has been through the OG
            // generator, so only claim the 1.91:1 dimensions when we know
            // that's actually what was served (older/un-backfilled rows
            // fall back to the raw photo, whatever shape it is).
            'ogImageWidth' => $featuredImage?->og_path ? OgImageGenerator::WIDTH : null,
            'ogImageHeight' => $featuredImage?->og_path ? OgImageGenerator::HEIGHT : null,
            'ogType' => $listing->isCar() ? 'vehicle' : 'product',
            'schemaJsonLd' => $listing->schemaJsonLd(),
        ]);
    }

    public function sitemap(): Response
    {
        $listings = Listing::query()
            ->where('status', 'available')
            ->latest('updated_at')
            ->get();

        $categories = PartCategory::cases();
        $chassisList = Listing::chassisSuggestions();

        $content = view('catalog.sitemap', compact('listings', 'categories', 'chassisList'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
