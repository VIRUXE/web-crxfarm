# CRX Farm

A free, open-source parts catalog for [CRX Farm](https://www.facebook.com/jeremiah.freeman.116318) — Jeremiah Freeman's Honda parts yard in Rossville, KS. Built to replace an all-Messenger/Facebook workflow with a real browsable, searchable catalog.

Stack: **Laravel 13 + htmx + MySQL**, server-rendered Blade (no JS build step, no SPA framework). Deployed as a subpath at `https://flaviopereira.dev/crxfarm`.

## What it does

- Public catalog: search + filter by chassis (CRX, EF, EG, Del Sol, EK, Accord, CRV, ...), individually-priced parts and per-donor-car entries (for cars too big to itemize part-by-part — "here's what's already pulled, ask about the rest").
- Every listing supports unlimited photos.
- Every listing links out to Messenger ("Ask about this") — pricing/shipping is still quoted by Jeremiah directly, this is a catalog + lead-gen front door, not a checkout.
- Simple password-protected `/admin` area so Jeremiah can add/edit listings and photos himself, no code required.
- `php artisan import:marketplace` — a one-off/rerunnable import from a SQLite staging file produced by a separate Facebook Marketplace scrape, so his existing live listings can seed the catalog.

## Local development

```bash
composer install
cp .env.example .env
# set DB_* to a local MySQL/MariaDB database, and ADMIN_SEED_PASSWORD
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan db:seed          # creates the admin user + a few placeholder listings
php artisan import:marketplace   # optional: pulls in real scraped data if present
php artisan serve
```

Visit `http://127.0.0.1:8000`, admin at `/admin/login`.

## Deploying to flaviopereira.dev/crxfarm

This app is served from a **subpath**, not domain root, following the same pattern as the other Laravel app already on this box (`/garagem504`). Steps:

1. Copy/clone this repo to `/var/www/crxfarm`, `composer install --no-dev -o`, set up `.env` for production (real `DB_PASSWORD`, `APP_KEY` via `php artisan key:generate`, `ADMIN_SEED_PASSWORD`), run `php artisan migrate --force`, `php artisan storage:link`, `php artisan db:seed --force` once, then `php artisan import:marketplace` to pull in real inventory.
2. Add an nginx location block to `/etc/nginx/sites-available/flaviopereira.dev` mirroring the `/garagem504` block:

    ```nginx
    location = /crxfarm { return 301 /crxfarm/; }
    location /crxfarm/ {
        alias /var/www/crxfarm/public/;
        index index.php;
        try_files $uri $uri/ @crxfarm;
        location ~ ^/crxfarm/(.+\.php)$ {
            include fastcgi_params;
            fastcgi_pass unix:/run/php/php8.5-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/crxfarm/public/$1;
            fastcgi_param SCRIPT_NAME /crxfarm/$1;
            fastcgi_param DOCUMENT_ROOT /var/www/crxfarm/public;
        }
    }
    location @crxfarm {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/crxfarm/public/index.php;
        fastcgi_param SCRIPT_NAME /crxfarm/index.php;
        fastcgi_param DOCUMENT_ROOT /var/www/crxfarm/public;
        fastcgi_param REQUEST_URI $request_uri;
    }
    ```

3. `nginx -t` to validate, back up the existing config first, then `systemctl reload nginx`.
4. Make sure `storage/` and `bootstrap/cache/` are writable by `www-data`, and `public/storage` is the symlink created by `artisan storage:link`.

## Notes / judgment calls

- Admin auth is Laravel's normal session auth with a single seeded admin user (`jeremiah@crxfarm.local`) rather than a full multi-user system — deliberately minimal for a one-operator shop.
- Pricing is free-text (`"$160"`, `"$100-150"`, or blank = "ask") rather than a strict numeric column, since Jeremiah doesn't have exact prices for everything and often quotes ranges.
- Donor-car listings use a `missing_parts` free-text field instead of a full parts-per-car breakdown table — matches how he actually inventories (state what's gone, not what's left).
