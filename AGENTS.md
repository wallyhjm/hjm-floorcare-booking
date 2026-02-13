# AGENTS.md

## Project Overview
This is a custom WordPress plugin for the H.J. Martin Floorcare Booking platform.

The plugin handles:
- Customer service booking forms
- Scheduling logic
- Google Places address autocomplete
- AJAX-driven UI updates
- Admin-side booking management

Tech stack:
- PHP 8.x (WordPress plugin architecture)
- MySQL (via $wpdb)
- JavaScript (jQuery)
- WordPress AJAX
- Google Maps / Places APIs

---

## Environment

- Hosted on Plesk (Linux)
- Git-based deployment (push may auto-deploy)
- WordPress coding standards required
- Production safety is critical

---

## File Structure

- `hjm-floorcare-booking.php` → main bootstrap file
- `/inc/` → core PHP logic (AJAX handlers, services, DB access)
- `/assets/js/` → frontend scripts
- `/assets/css/` → styles
- `/templates/` → optional frontend markup

Shared logic must live in `/inc/`.  
Avoid putting business logic in the main plugin file.

---

## Database Standards

### Table Prefix
WordPress is configured with:

```
flcwp_
```

When using `$wpdb`, always append only the table suffix:

```php
$table = $wpdb->prefix . 'bookings';
```

Do not append `flcwp_` again in code.

### Example Tables
- flcwp_bookings
- flcwp_services
- flcwp_locations

### Query Rules

- Always use `$wpdb->prepare()` for dynamic values
- Never concatenate raw user input into SQL
- Sanitize before query, escape before output

### Query Pattern

```php
global $wpdb;

$table = $wpdb->prefix . 'bookings';

$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d",
    $booking_id
);

$result = $wpdb->get_row($sql);
```

---

## AJAX Architecture

All dynamic interactions must use WordPress AJAX:

- `wp_ajax_{action}`
- `wp_ajax_nopriv_{action}` (for public booking actions)

### Requirements

- Always verify nonce
- Always sanitize input
- Always return JSON using:

```php
wp_send_json_success($data);
wp_send_json_error($message);
```

- Never echo raw JSON manually

---

## Input Handling

### Required Sanitization

- `sanitize_text_field()`
- `sanitize_email()`
- `intval()`
- `floatval()`

### Output Escaping

- `esc_html()`
- `esc_attr()`
- `esc_url()`

Never trust `$_POST` or `$_GET`.

---

## JavaScript Standards

- Use jQuery (bundled with WordPress)
- Use event delegation for dynamic content
- Avoid inline JS in templates
- Separate logic into reusable functions

### Pattern

```js
$(document).on('click', '.flcwp-button', function () {
    // handler logic
});
```

---

## Google API Usage

The plugin uses:
- Google Maps API
- Google Places API

### Rules

- API keys must NOT be hardcoded
- Keys must be defined in `wp-config.php`:

```php
define('HJM_GOOGLE_API_KEY', '');
define('HJM_GOOGLE_PLACES_API_KEY', '');
```

- Pass keys to JS via `wp_localize_script`
- Avoid unnecessary API calls
- Cache results when possible

---

## Booking Logic Rules

- Treat booking submissions as transactional
- Validate required fields server-side
- Never rely solely on frontend validation
- Prevent duplicate submissions
- Normalize address data before storing

---

## Security Requirements

- Nonce verification required for all AJAX
- Escape all output
- Do not expose internal IDs unnecessarily
- Do not leak API keys
- Do not bypass capability checks in admin context

---

## Modification Guidelines

When modifying code:

- Maintain backward compatibility
- Do not break existing AJAX endpoints
- Search entire repo before renaming functions
- Keep business logic modular
- Avoid duplicating query logic

Prefer extending existing functions over rewriting them.

---

## Performance Considerations

- Avoid large unbounded queries in AJAX handlers
- Add indexes to `flcwp_` tables when appropriate
- Cache expensive lookups
- Minimize Google API calls

---

## Debugging

- Use `error_log()` for PHP debugging
- Use browser console for JS debugging
- Confirm AJAX response structure before modifying frontend
- Validate nonce issues first when AJAX fails

---

## Critical Do / Do Not

### Do
- Use `$wpdb->prepare`
- Sanitize input
- Escape output
- Verify nonces
- Keep logic reusable

### Do Not
- Hardcode API keys
- Echo raw JSON
- Trust client-side validation
- Modify main bootstrap file unnecessarily
- Break production-safe patterns

---

## Agent Behavior Expectations

When adding features:

- Follow existing naming conventions
- Reuse patterns in this document
- Maintain security and validation rules
- Keep frontend and backend responsibilities separated
