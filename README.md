# CRX Farm

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat&logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=flat&logo=tailwindcss)](https://tailwindcss.com)
[![htmx](https://img.shields.io/badge/htmx-2.0%2B-3366CC?style=flat)](https://htmx.org)
[![WebAuthn](https://img.shields.io/badge/Auth-WebAuthn%20Passkeys-green?style=flat)](https://w3c.github.io/webauthn/)
[![Open Source](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A modern, open-source parts catalog and lead-generation portal built for **[CRX Farm](https://www.facebook.com/profile.php?id=100083512851607)** — Jeremiah Freeman's Honda parts yard in Rossville, Kansas.

CRX Farm replaces a manual Facebook Marketplace / Messenger workflow with a dedicated, searchable, SEO-optimized web inventory. Potential buyers can browse individual parts and complete donor cars, filter by chassis/category/bolt pattern, view high-resolution photo galleries and demo videos, and click directly through to Facebook Messenger for personalized quotes and shipping.

* **Live Site:** [flaviopereira.dev/crxfarm](https://flaviopereira.dev/crxfarm)
* **GitHub Repository:** [github.com/VIRUXE/web-crxfarm](https://github.com/VIRUXE/web-crxfarm)

---

## Table of Contents

- [Overview & Architecture](#overview--architecture)
- [Key Features](#key-features)
- [Authentication: Passkeys + PIN Fallback](#authentication-passkeys--pin-fallback)
- [Media Processing Pipeline](#media-processing-pipeline)
- [System Requirements](#system-requirements)
- [Step-by-Step Local Setup](#step-by-step-local-setup)
- [CLI Tools & Commands](#cli-tools--commands)
- [Testing](#testing)
- [Production Deployment](#production-deployment)
  - [Cloudflare R2 Storage](#cloudflare-r2-storage)
  - [Nginx Subpath Configuration](#nginx-subpath-configuration)
- [Contributing & License](#contributing--license)

---

## Overview & Architecture

CRX Farm is designed with a lightweight, high-performance tech stack prioritizing simplicity and maintainability without frontend build bloat:

* **Backend:** Laravel 13 running on PHP 8.3+ with strict type declarations, native Enums, and constructor promotion.
* **Frontend:** Server-rendered Laravel Blade + **htmx** for instant search and dynamic filtering without page reloads.
* **Styling & UI:** **Tailwind CSS v4** with **Blade Lucide Icons** for a responsive, mobile-first design.
* **Database:** MySQL / MariaDB (production & dev) or SQLite (testing & local dev).
* **Media & Assets:** Automated image border trimming, watermarking, WebP encoding, OpenGraph generation (via GD/FreeType), and WebM video conversion (via FFmpeg).
* **Storage:** Dual-mode filesystem: local symlinked disk for development, **Cloudflare R2** (S3-compatible) for production.
* **Authentication:** Passwordless, email-free WebAuthn / Passkeys (Level 3) powered by `laravel/passkeys` with a 6-digit PIN bootstrap and fallback.

```
┌─────────────────────────────────────────────────────────────┐
│                      Public Visitors                        │
│   • Live Search & Chassis / Category Filters (htmx)         │
│   • Multi-photo gallery, WebM videos, Donor Car Part-Outs   │
│   • Schema.org JSON-LD + 1200x630 OpenGraph SEO Cards       │
│   • Direct Messenger Linkout ("Ask about this")             │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│                    Laravel 13 Application                   │
│   • CatalogController (public browsable & sitemap)          │
│   • Admin/ListingController (CRUD, Image Manager)           │
│   • Admin/UserController (device passkey management)        │
│   • TitleNormalizer & DescriptionCleaner Pipelines          │
│   • ImageTrimmer & VideoConverter Support Services          │
└──────────────┬──────────────────────────────┬───────────────┘
               │                              │
┌──────────────▼─────────────┐ ┌──────────────▼───────────────┐
│     Database Layer         │ │     Storage / Media Layer    │
│  • Listings (Parts / Cars) │ │  • Local Storage Disk / R2   │
│  • Chassis & Pivot Tables  │ │  • Watermarked WebP Photos   │
│  • Listing Images & Videos │ │  • 1200x630 OG Social JPEGs  │
│  • Users & Passkeys        │ │  • VP9/Opus WebM Videos      │
└────────────────────────────┘ └──────────────────────────────┘
```

---

## Key Features

### 1. Public Inventory Catalog
- **Chassis Fitment Filtering:** Filter by Honda/Acura chassis codes: CRX, EF, EG, EK, Del Sol, DA Integra, DC2 Integra, Prelude, Accord, CR-V, Element, S2000, Civic Wagon, Fit, etc.
- **Part Categories & Tags:** Grouped into Engine & Drivetrain, Exterior & Body, Interior, Lighting & Electrical, Suspension & Brakes, Wheels & Tires, Exhaust & Intake, and Other/Misc.
- **Wheel Bolt Patterns:** Dedicated filter for `4x100`, `4x114.3`, `5x114.3`, and `5x120` patterns.
- **Dual Listing Types:**
  - *Individual Parts:* Specific parts with compatible chassis pivot bindings and category tags.
  - *Donor Cars:* Whole vehicles parted out over time, featuring dynamic "Missing Parts" checklists.
- **Direct Lead-Gen:** Click-to-message buttons linking directly to Facebook Messenger (`m.me/jeremiah.freeman.116318`) with pre-filled context.
- **Full SEO Optimization:** Dynamic meta tags, canonical URLs, XML sitemap (`/sitemap.xml`), and Schema.org `Product` / `Vehicle` JSON-LD structured data.

### 2. Admin Control Center (`/admin`)
- **Searchable Dashboard:** Real-time search across titles, descriptions, chassis, and bolt patterns.
- **Listing Editor:** Multi-photo and video upload, custom thumbnail chooser, dynamic chassis quick-picks, and autocomplete suggestions.
- **Visual Image Manager (`/admin/images`):** Grid view of all inventory photos with quick delete, re-sequencing, and filters to identify listings missing media.
- **User & Device Management (`/admin/users`):** Add operators, view enrolled passkey counts, issue PINs, revoke device access, and manage administrative privileges.

---

## Authentication: Passkeys + PIN Fallback

CRX Farm uses a modern **passwordless, email-free** authentication model. Admins authenticate with hardware-backed passkeys (Face ID, Touch ID, Windows Hello, YubiKey) without remembering passwords or relying on email delivery.

```
             ┌──────────────────────────────────────────────┐
             │       Admin Creates User via /admin/users    │
             │   (Enters username -> Generates 6-digit PIN) │
             └──────────────────────┬───────────────────────┘
                                    │
                                    ▼
             ┌──────────────────────────────────────────────┐
             │         User Logs In with PIN at /admin      │
             │     (Session created with status: pin_set)   │
             └──────────────────────┬───────────────────────┘
                                    │
                                    ▼
             ┌──────────────────────────────────────────────┐
             │ Mandatory Passkey Enrollment (/onboarding)   │
             │   (WebAuthn ceremony via browser credentials)│
             └──────────────────────┬───────────────────────┘
                                    │
                     ┌──────────────┴──────────────┐
                     ▼                             ▼
        [First-Time User]                  [Existing User on New Device]
      Status flips to active             must_enroll_passkey flag cleared
                     │                             │
                     └──────────────┬──────────────┘
                                    ▼
             ┌──────────────────────────────────────────────┐
             │           Full Access to /admin/*            │
             │      (Subsequent logins: 1-click Passkey)    │
             └──────────────────────────────────────────────┘
```

### Flow Breakdown:
1. **Invite / User Creation:** An existing admin creates an operator by `username` in `/admin/users/invite`. The system generates a unique 6-digit PIN displayed once on screen to hand to the user.
2. **PIN Login:** The operator visits `/admin`, clicks *"Use your PIN instead"*, and enters their 6-digit PIN (rate-limited to 5 attempts per 15 minutes).
3. **Mandatory Passkey Enrollment:** Signing in with a PIN routes the user directly to `/onboarding/passkey`. The `EnsureUserIsActive` middleware blocks access to `/admin/*` until a passkey is registered on the device.
4. **Activation:** Completing the WebAuthn ceremony registers the credential and flips the user status to `active`.
5. **Ongoing Authentication:** Daily logins use the primary **Passkey Login** button (1-click WebAuthn ceremony).
6. **Multi-Device & Recovery:**
   - *Adding a new device:* Sign in with the 6-digit PIN on the new device; the system prompts to register a passkey for that device.
   - *Lost / Compromised device:* An admin clicks *"Reset access"* in `/admin/users`, instantly revoking all active passkeys and resetting the account to `pin_set`.

---

## Media Processing Pipeline

CRX Farm automatically processes all uploaded and scraped inventory media:

- **Border Trimming (`ImageTrimmer::trim`):** Scans image edges for letterboxing/black bars (common in Facebook mobile screenshots) and crops them cleanly.
- **Downscaling:** Resizes large camera photos to a max dimension of 1600px, keeping galleries lightweight.
- **Staggered Watermarking:** Applies a translucent, tiled `CRXFARM` watermark at a 30° angle across the canvas with drop shadows. This prevents unauthorized scraper reuse on Marketplace scams while keeping parts visible.
- **WebP Conversion:** Converts all stored images to optimized WebP at quality 82–90.
- **Social Preview Cards (`OgImageGenerator`):** Automatically generates a 1200x630 (1.91:1) cover-cropped JPEG (`og_path`) for each listing photo, ensuring crisp display on Facebook, Discord, X, and iMessage previews.
- **Video Conversion (`VideoConverter`):** If FFmpeg is installed, incoming videos (MP4, MOV, AVI) are converted to WebM (VP9 video + Opus audio) with the watermark burned in, and an initial WebP poster frame is extracted.

---

## System Requirements

Before setting up CRX Farm, ensure your environment has:

| Requirement | Minimum Version | Notes |
| :--- | :--- | :--- |
| **PHP** | `^8.3` | Required extensions: `pdo_mysql` (or `pdo_sqlite`), `gd`, `bcmath`, `mbstring`, `openssl`, `curl`, `xml`, `zip` |
| **Composer** | `2.x` | PHP package manager |
| **Node.js & npm** | `Node 18+` / `npm 9+` | For Tailwind CSS v4 & asset bundling via Vite |
| **Database** | MySQL 8.0+ / MariaDB 10.5+ | SQLite is also supported for testing and local dev |
| **FFmpeg** *(optional)* | `4.x+` | Required only if processing and converting video uploads |
| **TrueType Fonts** *(optional)* | FreeType / Liberation / DejaVu | Used for rendering text watermarks on images |

---

## Step-by-Step Local Setup

Follow these steps to run CRX Farm on any local development machine:

### 1. Clone the Repository
```bash
git clone https://github.com/VIRUXE/web-crxfarm.git crxfarm
cd crxfarm
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js packages
npm install
```

### 3. Configure Environment
```bash
# Copy example environment file
cp .env.example .env
```

Open `.env` in your editor and configure your environment:
```ini
APP_NAME="CRX Farm"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
ASSET_URL=http://127.0.0.1:8000

# Database Configuration (MySQL / MariaDB):
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crxfarm
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Or for quick SQLite setup:
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database/database.sqlite

# Local Dev Admin Seed PIN:
ADMIN_SEED_PIN=123456

# Keep filesystem driver as local for dev:
FILESYSTEM_DISK=local
```

### 4. Initialize the Application
```bash
# Generate application encryption key
php artisan key:generate

# Run database migrations
php artisan migrate

# Create the public storage symlink for uploaded media
php artisan storage:link

# Seed initial admin user and sample catalog listings
php artisan db:seed
```

### 5. Build Frontend Assets
```bash
# Build production assets
npm run build

# Or run the Vite dev server with Hot Module Reloading (HMR):
npm run dev
```

### 6. Start the Development Server
```bash
php artisan serve
```

### 7. Access the Catalog & Admin
1. Open your browser and navigate to `http://127.0.0.1:8000` to view the public catalog.
2. Go to `http://127.0.0.1:8000/admin` to access the admin area.
3. Click **"Use your PIN instead"**.
4. Enter the seeded PIN: `123456`.
5. You will land on the **Passkey Enrollment** screen (`/onboarding/passkey`). Click **"Register Passkey"** and complete your browser's WebAuthn prompt (Touch ID, Windows Hello, or security key).
6. Your account is now `active`, and you will have full access to manage listings and users!

---

## CLI Tools & Commands

CRX Farm includes specialized Artisan console commands for inventory management and maintenance:

### 1. Facebook Marketplace Scrape Importer
Imports raw scraped listings from a SQLite database or JSONL file into the catalog, automatically classifying vehicles vs parts, assigning categories and chassis, and processing images:
```bash
php artisan import:marketplace --path=/path/to/listings.jsonl
# Or from a scrape SQLite file with a custom images folder:
php artisan import:marketplace --path=/path/to/scrape.sqlite --images=/path/to/images --honda-only
```
- `--path`: Path to `listings.jsonl` or SQLite file.
- `--images`: Directory containing raw image files (defaults to `crxfarm_images` next to the file).
- `--honda-only`: Filters out lawnmowers, boats, jet skis, and non-Honda listings.
- `--force`: Re-extracts and overwrites photos even if already imported.

### 2. Title Normalizer
Standardizes listing titles into Title Case while enforcing correct capitalization for engine codes (B16A, D16Z6, K20A, H22), chassis codes (EF, EG, EK, DC2), brands (Hasport, Skunk2, Mugen), and acronyms (OEM, JDM, VTEC, ECU, LSD):
```bash
# Dry run to inspect changes without saving:
php artisan listings:normalize-titles --dry-run

# Apply normalization across all listings:
php artisan listings:normalize-titles
```

### 3. Description Cleaner
Removes Facebook Marketplace artifacts (`[hidden information]`), "PM me for price" boilerplate, and seller signature noise while preserving technical specs:
```bash
# Dry run:
php artisan listings:clean-descriptions --dry-run

# Apply description cleaning:
php artisan listings:clean-descriptions
```

### 4. Backfill OpenGraph Images
Generates 1200x630 social card variants for any existing listing photos:
```bash
php artisan app:backfill-og-images
```

---

## Testing

CRX Farm includes a comprehensive test suite covering catalog queries, SEO metadata generation, image/video processing, title normalization, and passkey authentication flows.

Tests run against an in-memory SQLite database (`.env.testing` / `phpunit.xml`):

```bash
# Run all tests via Artisan
php artisan test

# Or run PHPUnit directly
vendor/bin/phpunit

# Run a specific test class
php artisan test tests/Feature/CatalogSeoTest.php
```

---

## Production Deployment

### Cloudflare R2 Storage

Production uses Cloudflare R2 (S3-compatible object storage) for zero-egress fee media hosting. The `public` disk seamlessly switches to R2 when configured:

Set the following in your production `.env`:
```ini
FILESYSTEM_PUBLIC_DRIVER=s3
FILESYSTEM_PUBLIC_URL=https://pub-<your-r2-id>.r2.dev
R2_ACCESS_KEY_ID=your_r2_access_key
R2_SECRET_ACCESS_KEY=your_r2_secret_key
R2_REGION=auto
R2_BUCKET=crxfarm-media
R2_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
R2_USE_PATH_STYLE_ENDPOINT=true
```

### Nginx Subpath Configuration

If deploying the application under a subpath (e.g. `https://yourdomain.com/crxfarm`), use an Nginx alias block with FastCGI parameter rewrites:

```nginx
# Redirect bare subpath to trailing slash
location = /crxfarm {
    return 301 /crxfarm/;
}

# Subpath assets & application handler
location /crxfarm/ {
    alias /var/www/crxfarm/public/;
    index index.php;
    try_files $uri $uri/ @crxfarm;

    location ~ ^/crxfarm/(.+\.php)$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/crxfarm/public/$1;
        fastcgi_param SCRIPT_NAME /crxfarm/$1;
        fastcgi_param DOCUMENT_ROOT /var/www/crxfarm/public;
    }
}

location @crxfarm {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /var/www/crxfarm/public/index.php;
    fastcgi_param SCRIPT_NAME /crxfarm/index.php;
    fastcgi_param DOCUMENT_ROOT /var/www/crxfarm/public;
    fastcgi_param REQUEST_URI $request_uri;
}
```

### Production Optimizations
```bash
# Optimize configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build optimized frontend assets
npm run build
```

---

## Contributing & License

Contributions are welcome! Please open an issue or submit a pull request on GitHub.

This project is open-source software licensed under the **[MIT License](LICENSE)**.
