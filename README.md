# WP Production Snippets

Curated, production-ready WordPress snippets for real-world WordPress projects.

## Structure

- `admin/` – Admin UI and dashboard tweaks
- `security/` – Security-related snippets
- `performance/` – Speed and optimization
- `frontend/` – Theme/frontend helpers
- `woocommerce/` – WooCommerce-specific utilities
- etc

## Usage

Each snippet is a standalone `.php` file.

- Copy its contents into your theme's `functions.php`, **or**
- Include it in a custom functionality plugin (e.g., Code Snippets).

## Snippets

### Editable Username on Profile Page

Allows administrators or users editing their own profile to change their WordPress username (`user_login`) from the profile screen.

**File:** `admin/editable-username.php`

**Important:** WordPress does not allow username changes by default.  
This snippet performs a direct database update – use with care in production.

**Tested:** WordPress 6.x

### Live Clock (Shortcode + Footer)

Displays a real-time clock showing either:

- The site's timezone, or
- The visitor's local timezone.

**Shortcode:** `[live_clock]`

**Settings (inside the file):**

- `mode` – `'site'` or `'visitor'`
- `site_timezone` – e.g. `Africa/Lagos`, `Europe/London`
- `site_label` – label shown when `mode = 'site'`
- `show_in_footer` – `true` to auto-output in the footer, `false` for shortcode only

**File:** `frontend/live-clock-shortcode.php`

**Tested:** WordPress 6.x

### Remove Category & Tag Base

Removes `/category/` and `/tag/` from default WordPress taxonomy URLs.

**Examples:**

- `/category/news/` → `/news/`
- `/category/parent/child/` → `/parent/child/`
- `/tag/design/` → `/design/`

**Features:**

- Supports hierarchical categories  
- Preserves existing posts and pages  
- Resolves base-less category and tag archives  
- Handles `/something/page/2` pagination  
- 301 redirects old `/category/...` and `/tag/...` URLs  
- Flushes rewrite rules once

**File:** `seo/remove-category-tag-base.php`

**Warning:** May conflict with pages or custom post types that share slugs, or with SEO plugins that modify rewrites. Test on staging before production.

**Tested:** WordPress 6.x

### Time‑Based Style Variation Switcher

Automatically switches a block theme’s style variation (`/styles/*.json`) based on the site’s local time.

- 07:00–13:00 → default theme.json
- 13:00–19:00 → styles/parchment.json
- 19:00–07:00 → styles/inverted.json

**File:** `block-theme/time-based-style-variation-switcher.php
`  
**Tested:** WordPress 6.x, block themes only.

