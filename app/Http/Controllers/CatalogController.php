<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::query()
            ->where('status', 'available')
            ->search($request->query('q'))
            ->chassis($request->query('chassis'))
            ->with(['images' => fn ($q) => $q->limit(1)])
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $chassisOptions = Listing::query()
            ->whereNotNull('chassis')
            ->distinct()
            ->orderBy('chassis')
            ->pluck('chassis');

        $view = $request->boolean('partial') || $request->header('HX-Request')
            ? 'catalog.partials.grid'
            : 'catalog.index';

        return view($view, compact('listings', 'chassisOptions'));
    }

    public function show(Listing $listing)
    {
        $listing->load('images');

        return view('catalog.show', compact('listing'));
    }
}
