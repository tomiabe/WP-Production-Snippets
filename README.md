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
