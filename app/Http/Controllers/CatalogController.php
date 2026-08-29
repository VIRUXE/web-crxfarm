<?php

namespace App\Http\Controllers;

use App\Enums\PartCategory;
use App\Models\Chassis;
use App\Models\Listing;
use Illuminate\Http\Request;
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
            ->with(['images' => fn ($q) => $q->limit(1), 'compatibleChassis'])
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

        $view = $request->boolean('partial') || $request->header('HX-Request')
            ? 'catalog.partials.grid'
            : 'catalog.index';

        return view($view, compact('listings', 'chassisOptions', 'categories'));
    }

    public function show(Listing $listing): View
    {
        $listing->load(['images', 'compatibleChassis']);

        return view('catalog.show', compact('listing'));
    }
}
