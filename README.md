# CRX Farm

A free, open-source parts catalog for [CRX Farm](https://www.facebook.com/jeremiah.freeman.116318) - Jeremiah Freeman's Honda parts yard in Rossville, KS. Built to replace an all-Messenger/Facebook workflow with a real browsable, searchable catalog.

Stack: **Laravel 13 + htmx + MySQL**, server-rendered Blade (no JS build step, no SPA framework). Deployed as a subpath at `https://flaviopereira.dev/crxfarm`.

## What it does

- Public catalog: search + filter by chassis (CRX, EF, EG, Del Sol, EK, Accord, CRV, ...), individually-priced parts and per-donor-car entries (for cars too big to itemize part-by-part - "here's what's already pulled, ask about the rest").
- Every listing supports unlimited photos.
- Every listing links out to Messenger ("Ask about this") - pricing/shipping is still quoted by Jeremiah directly, this is a catalog + lead-gen front door, not a checkout.
- Passkey-first `/admin` area (see "Authentication" below) so Jeremiah can add/edit listings and photos himself, no code required.
- `php artisan import:marketplace` - a one-off/rerunnable import from a SQLite staging file produced by a separate Facebook Marketplace scrape, so his existing live listings can seed the catalog.

## Authentication: invite -> PIN -> mandatory passkey

There's no traditional password login. New admin users only ever get created by an existing admin, and go through one fixed onboarding path:

1. **Invite** (`/admin/users/invite`, admin-only) - enter a name + email. This creates a `User` row in `status = invited` (no credentials at all yet) and generates a 48-hour, one-time signed magic link (`URL::temporarySignedRoute`) to `/onboarding/pin/{user}`.
2. **PIN setup** (the magic link) - the invitee sets a 6-digit PIN (hashed into `pin_hash`, bcrypt via Laravel's `hashed` cast). This flips `status` to `pin_set` and immediately logs them in - not because PIN alone is meant to grant access, but because the next step's API (passkey registration) requires an authenticated session.
3. **Mandatory passkey enrollment** (`/onboarding/passkey`) - using [`laravel/passkeys`](https://github.com/laravel/passkeys) (the official first-party WebAuthn package). The `EnsureUserIsActive` middleware (aliased `active`, applied to every real `/admin/*` route) redirects any authenticated-but-`status != active` user back here - there is no way to reach the catalog admin area without a passkey existing. On successful registration, `App\Listeners\ActivateUserOnPasskeyRegistered` (listening for the package's `PasskeyRegistered` event) flips `status` to `active`.
4. **Login, going forward** - passkey is primary (`/admin/login`, WebAuthn ceremony against `laravel/passkeys`' own `/passkeys/login/*` routes). "Use your PIN instead" is a fallback form (`POST /admin/login/pin`, throttled `5` attempts / `15` minutes) - it authenticates against `pin_hash`, but a user who's only reached `pin_set` (never finished enrollment) gets routed straight back to the mandatory enroll screen instead of the admin area, same as case 3. A PIN never grants admin access by itself.

No npm/build step: the WebAuthn ceremony is done with **vanilla JS** (`public/js/passkey-onboarding.js`) using the browser's native `PublicKeyCredential.parseCreationOptionsFromJSON` / `parseRequestOptionsFromJSON` + `credential.toJSON()` (WebAuthn Level 3), instead of the `@laravel/passkeys` npm package.

**Known gaps / judgment calls:**
- **Mail isn't configured yet** (`MAIL_MAILER=log`). The invite still builds and sends a real `App\Mail\UserInvited` Mailable (so nothing needs touching once real mail is set up), but *right now* it also flashes the link directly on the invite confirmation page, and it lands in `storage/logs/laravel.log` - that's how you actually get the link to send someone today.
- **WebAuthn ceremonies can't be tested outside a real browser.** Everything up to and including the registration/verification *options* endpoints was verified working (curl/tinker), and the full invite -> PIN -> "blocked from admin until passkey exists" -> "PIN fallback still routes to the enroll screen, not around it" flow was verified end-to-end for a non-active user. The actual `navigator.credentials.create()`/`.get()` browser ceremony has not been exercised by anything but a human with an authenticator - test that by hand once deployed.
- **A signed magic link is tied to the app's configured `APP_URL`/path.** Generating one against production config (`https://flaviopereira.dev/crxfarm/...`) and hitting it against a differently-pathed local dev server (plain `php artisan serve`, no `/crxfarm` prefix) will 403 on signature validation - that's expected, not a bug; it validates correctly once actually served under the real subpath via nginx (same reason the rest of this app needs the nginx alias + `SCRIPT_NAME` rewrite, see "Deploying" below).
- **PIN is a fallback for one specific already-enrolled-or-enrolling user, not a second independent factor or an alternate signup path.** A 6-digit PIN has far less entropy than a passkey; the throttle (5/15min) is the main defense, plus the fact that it can never reach the admin area without a passkey existing for that account.
- The old seeded-admin flow (`ADMIN_SEED_PASSWORD` + plain password login) is gone. `AdminUserSeeder` now seeds directly into `status = pin_set` with a PIN from `ADMIN_SEED_PIN` (default `123456`) - a seeder can't perform a real WebAuthn ceremony, so even the seeded dev admin still has to finish passkey enrollment once through the actual browser flow (log in with the seed PIN via "use your PIN instead", you'll land on `/onboarding/passkey`).

## Local development

```bash
composer install
cp .env.example .env
# set DB_* to a local MySQL/MariaDB database, and (optionally) ADMIN_SEED_PIN
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan db:seed          # seeds the admin user (status=pin_set, PIN from ADMIN_SEED_PIN, default 123456) + a few placeholder listings
php artisan import:marketplace   # optional: pulls in real scraped data if present
php artisan serve
```

Visit `http://127.0.0.1:8000`, admin at `/admin/login` - click "Use your PIN instead", sign in with `jeremiah@crxfarm.local` / the seed PIN, then finish passkey enrollment on the page it lands you on (`/onboarding/passkey`). `php artisan serve` on `localhost`/`127.0.0.1` is a browser-trusted secure context, so WebAuthn works without HTTPS locally.

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

### Listing photos & videos (Cloudflare R2)

Production serves listing photos/videos from Cloudflare R2 instead of local disk - the `public` filesystem disk (`config/filesystems.php`) switches from `local` to `s3` when `FILESYSTEM_PUBLIC_DRIVER=s3` is set, pointed at R2 via its S3-compatible API. The disk name didn't change, so every `Storage::disk('public')` call site in the app is unaffected either way.

Set these in production `.env` (leave `FILESYSTEM_PUBLIC_DRIVER` unset in local dev to keep using local disk + `artisan storage:link`):

```
FILESYSTEM_PUBLIC_DRIVER=s3
FILESYSTEM_PUBLIC_URL=https://pub-24013a09b0c344bda771e681dea90f9e.r2.dev
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_REGION=auto
R2_BUCKET=crxfarm-media
R2_ENDPOINT=https://<cloudflare-account-id>.us.r2.cloudflarestorage.com
R2_USE_PATH_STYLE_ENDPOINT=true
```

The `crxfarm-media` R2 bucket is created under the `us` jurisdiction (hard US data-residency guarantee, not just a location hint) since this catalog is US-facing. Its R2 API token is scoped to just this bucket (Object Read & Write) - not an account-wide token.

## Notes / judgment calls

- Admin auth is Laravel's normal session auth with a single seeded admin user (`jeremiah@crxfarm.local`) rather than a full multi-user system - deliberately minimal for a one-operator shop.
- Pricing is free-text (`"$160"`, `"$100-150"`, or blank = "ask") rather than a strict numeric column, since Jeremiah doesn't have exact prices for everything and often quotes ranges.
- Donor-car listings use a `missing_parts` free-text field instead of a full parts-per-car breakdown table - matches how he actually inventories (state what's gone, not what's left).
