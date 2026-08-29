{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach($categories as $cat)
        <url>
            <loc>{{ route('catalog.index', ['category' => $cat->value]) }}</loc>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    @foreach($chassisList as $chassis)
        <url>
            <loc>{{ route('catalog.index', ['chassis' => $chassis]) }}</loc>
            <changefreq>daily</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
    @foreach($listings as $listing)
        <url>
            <loc>{{ $listing->url() }}</loc>
            <lastmod>{{ $listing->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    @endforeach
</urlset>
