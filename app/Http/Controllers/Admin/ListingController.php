<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function index()
    {
        $listings = Listing::latest()->paginate(30);

        return view('admin.listings.index', compact('listings'));
    }

    public function create()
    {
        $listing = new Listing();

        return view('admin.listings.form', compact('listing'));
    }

    public function store(Request $request)
    {
        $listing = Listing::create($this->validated($request));

        $this->storeImages($request, $listing);

        return redirect()->route('admin.listings.edit', $listing)->with('status', 'Listing created.');
    }

    public function edit(Listing $listing)
    {
        $listing->load('images');

        return view('admin.listings.form', compact('listing'));
    }

    public function update(Request $request, Listing $listing)
    {
        $listing->update($this->validated($request));

        $this->storeImages($request, $listing);

        if ($request->header('HX-Request')) {
            $listing->load('images');

            return view('admin.listings.partials.form-fields', ['listing' => $listing, 'status' => 'Saved.']);
        }

        return redirect()->route('admin.listings.edit', $listing)->with('status', 'Saved.');
    }

    public function destroy(Listing $listing)
    {
        foreach ($listing->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $listing->delete();

        return redirect()->route('admin.listings.index')->with('status', 'Listing deleted.');
    }

    public function destroyImage(ListingImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $listingId = $image->listing_id;
        $image->delete();

        // Renumber remaining images so seq stays contiguous
        $images = ListingImage::where('listing_id', $listingId)->orderBy('seq')->get();
        foreach ($images as $i => $img) {
            $img->update(['seq' => $i]);
        }

        return response('', 200);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:part,car'],
            'title' => ['required', 'string', 'max:255'],
            'chassis' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'missing_parts' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:available,pending,sold'],
        ]);
    }

    private function storeImages(Request $request, Listing $listing): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $nextSeq = (int) $listing->images()->max('seq') + ($listing->images()->exists() ? 1 : 0);

        foreach ($request->file('images') as $file) {
            $path = $file->store('listings', 'public');
            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => $path,
                'seq' => $nextSeq++,
            ]);
        }
    }
}
