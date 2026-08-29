<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Http\Controllers\Controller;
use App\Models\Chassis;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Support\ImageTrimmer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(): View
    {
        $listings = Listing::with('compatibleChassis')->latest()->paginate(30);

        return view('admin.listings.index', compact('listings'));
    }

    public function create(): View
    {
        $listing = new Listing;
        $chassisOptions = Chassis::orderBy('name')->get();

        return view('admin.listings.form', compact('listing', 'chassisOptions'));
    }

    /**
     * Grid of every listing's photos for quick review and pruning of bad
     * images. Optionally filter to listings with no photos so gaps are easy to
     * find and fix.
     */
    public function images(Request $request): View
    {
        $onlyMissing = $request->boolean('missing');

        $listings = Listing::query()
            ->with('images')
            ->when($onlyMissing, fn ($q) => $q->doesntHave('images'), fn ($q) => $q->has('images'))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $totals = [
            'images' => ListingImage::count(),
            'missing' => Listing::doesntHave('images')->count(),
        ];

        return view('admin.images.index', compact('listings', 'totals', 'onlyMissing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $listing = Listing::create($this->validated($request));

        $this->syncChassis($request, $listing);
        $this->storeImages($request, $listing);

        return redirect()->route('admin.listings.edit', $listing)->with('status', 'Listing created.');
    }

    public function edit(Listing $listing): View
    {
        $listing->load(['images', 'compatibleChassis']);
        $chassisOptions = Chassis::orderBy('name')->get();

        return view('admin.listings.form', compact('listing', 'chassisOptions'));
    }

    public function update(Request $request, Listing $listing): View|RedirectResponse
    {
        $listing->update($this->validated($request));

        $this->syncChassis($request, $listing);
        $this->storeImages($request, $listing);

        if ($request->header('HX-Request')) {
            $listing->load(['images', 'compatibleChassis']);
            $chassisOptions = Chassis::orderBy('name')->get();

            return view('admin.listings.partials.form-fields', ['listing' => $listing, 'chassisOptions' => $chassisOptions, 'status' => 'Saved.']);
        }

        return redirect()->route('admin.listings.edit', $listing)->with('status', 'Saved.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        foreach ($listing->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $listing->delete();

        return redirect()->route('admin.listings.index')->with('status', 'Listing deleted.');
    }

    public function destroyImage(ListingImage $image): Response
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
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(ListingType::class)],
            'title' => ['required', 'string', 'max:255'],
            'chassis' => ['nullable', 'string', 'max:100'],
            'chassis_ids' => ['nullable', 'array'],
            'chassis_ids.*' => ['integer', 'exists:chassis,id'],
            'chassis_other' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::enum(PartCategory::class)],
            'price' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'missing_parts' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:available,pending,sold'],
        ]);

        unset($validated['chassis_ids'], $validated['chassis_other']);

        if (array_key_exists('missing_parts', $validated)) {
            if (is_array($validated['missing_parts'])) {
                $items = array_values(array_filter(
                    array_map('trim', $validated['missing_parts']),
                    fn (string $item) => $item !== ''
                ));
                $validated['missing_parts'] = ! empty($items) ? implode("\n", $items) : null;
            } elseif (is_string($validated['missing_parts'])) {
                $trimmed = trim($validated['missing_parts']);
                $validated['missing_parts'] = $trimmed !== '' ? $trimmed : null;
            }
        }

        return $validated;
    }

    private function syncChassis(Request $request, Listing $listing): void
    {
        if (! $listing->isPart()) {
            return;
        }

        $ids = collect($request->input('chassis_ids', []))->map(fn ($id) => (int) $id);

        $otherNames = collect(preg_split('/[,;\n]+/', (string) $request->input('chassis_other', '')) ?: [])
            ->map(fn (string $name) => trim($name))
            ->filter();

        $otherIds = $otherNames->map(fn (string $name) => Chassis::firstOrCreate(['name' => $name])->id);

        $listing->compatibleChassis()->sync($ids->merge($otherIds)->unique());
    }

    private function storeImages(Request $request, Listing $listing): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $nextSeq = (int) $listing->images()->max('seq') + ($listing->images()->exists() ? 1 : 0);

        foreach ($request->file('images') as $file) {
            $path = ImageTrimmer::storeUploaded($file, 'listings', 'public');
            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => $path,
                'seq' => $nextSeq++,
            ]);
        }
    }
}
