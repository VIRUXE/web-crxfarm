<?php

namespace App\Http\Controllers;

use App\Enums\PartCategory;
use App\Models\Chassis;
use App\Models\Listing;
use App\Support\OgImageGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->where('status', 'available')
            ->search($request->query('q'))
            ->chassis($request->query('chassis'))
            ->category($request->query('category'))
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

        return view($view, compact('listings', 'chassisOptions', 'categories', 'boltPatternOptions'));
    }

    public function show(Listing $listing): View
    {
        $listing->load(['images', 'compatibleChassis', 'thumbnailImage']);

        $descriptionParts = array_filter([
            $listing->isCar() ? 'Donor car' : 'Used Honda part',
            $listing->chassisLabel() !== '' ? 'fits '.$listing->chassisLabel() : null,
            (float) $listing->price > 0 ? '$'.rtrim(rtrim(number_format((float) $listing->price, 2), '0'), '.') : null,
        ]);

        $metaDescription = Str::of((string) $listing->description)->squish()->limit(120)->toString()
            ?: implode(' · ', $descriptionParts).' - CRX Farm, Rossville, Kansas. Message Jeremiah to ask about this listing.';

        $featuredImage = $listing->featuredImage();

        return view('catalog.show', [
            'listing' => $listing,
            'title' => $listing->title.' | CRX Farm',
            'metaDescription' => $metaDescription,
            'ogImage' => $featuredImage?->og_url,
            // og_path is only set once an image has been through the OG
            // generator, so only claim the 1.91:1 dimensions when we know
            // that's actually what was served (older/un-backfilled rows
            // fall back to the raw photo, whatever shape it is).
            'ogImageWidth' => $featuredImage?->og_path ? OgImageGenerator::WIDTH : null,
            'ogImageHeight' => $featuredImage?->og_path ? OgImageGenerator::HEIGHT : null,
            'ogType' => 'product',
        ]);
    }
}
