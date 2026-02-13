# PATTERNS.md

## Purpose
This file contains reusable code patterns for the Floorcare Booking plugin.

Agents should follow these patterns when generating or modifying code.

`AGENTS.md` is the policy-level source of truth. If this file changes naming, nonce, table, or AJAX patterns, update `AGENTS.md` in the same commit.

---

## AJAX Flow Pattern (Frontend -> Backend)

### JavaScript Request

```js
$.ajax({
    url: floorcareBookingContext.ajaxUrl,
    type: 'POST',
    dataType: 'json',
    data: {
        action: 'floorcare_set_booking',
        nonce: floorcareBookingContext.nonce,
        date: date,
        time: time
    },
    success: function (response) {
        if (response && response.success) {
            // handle success
        } else {
            // handle error
        }
    },
    error: function () {
        console.error('AJAX request failed');
    }
});
```

---

### PHP AJAX Handler

```php
add_action('wp_ajax_floorcare_set_booking', 'hjm_floorcare_ajax_set_booking');
add_action('wp_ajax_nopriv_floorcare_set_booking', 'hjm_floorcare_ajax_set_booking');

function hjm_floorcare_ajax_set_booking() {
    if (!check_ajax_referer('hjm_floorcare_ajax', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    $date = sanitize_text_field($_POST['date'] ?? '');
    $time = sanitize_text_field($_POST['time'] ?? '');

    if (empty($date) || empty($time)) {
        wp_send_json_error(['message' => 'Missing required fields'], 400);
    }

    $slots = hjm_floorcare_get_available_slots($date);

    if (!in_array($time, $slots, true)) {
        wp_send_json_error(['message' => 'Selected time is no longer available.'], 409);
    }

    WC()->session->set('floorcare_booking_date', $date);
    WC()->session->set('floorcare_booking_time', $time);

    wp_send_json_success(['message' => 'Booking time saved']);
}
```

---

## Database Insert Pattern

```php
function hjm_floorcare_insert_booking($order_id, $date, $start_time, $end_time, $duration_minutes, $address) {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    $inserted = $wpdb->insert(
        $table,
        [
            'order_id'         => (int) $order_id,
            'booking_date'     => $date,
            'start_time'       => $start_time,
            'end_time'         => $end_time,
            'duration_minutes' => (int) $duration_minutes,
            'service_address'  => $address,
            'status'           => 'confirmed',
            'created_at'       => current_time('mysql')
        ],
        [
            '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s'
        ]
    );

    return $inserted !== false;
}
```

---

## Database Query Pattern

```php
function hjm_floorcare_get_booking($id) {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, order_id, booking_date, start_time, end_time, duration_minutes, status
             FROM {$table}
             WHERE id = %d",
            (int) $id
        ),
        ARRAY_A
    );
}
```

---

## Update Pattern

```php
function hjm_floorcare_update_booking_status($id, $status) {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    return $wpdb->update(
        $table,
        ['status' => $status],
        ['id' => (int) $id],
        ['%s'],
        ['%d']
    );
}
```

---

## Script Localization Pattern

```php
wp_localize_script('hjm-floorcare-booking', 'floorcareBookingContext', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('hjm_floorcare_ajax')
]);
```

---

## Form Submission Pattern (jQuery)

```js
$(document).on('change', '[name="floorcare_booking_time"]', function () {
    const $container = $(this).closest('.floorcare-booking');
    const date = $container.find('[name="floorcare_booking_date"]').val();
    const time = $(this).val();

    $.post(floorcareBookingContext.ajaxUrl, {
        action: 'floorcare_set_booking',
        nonce: floorcareBookingContext.nonce,
        date: date,
        time: time
    }, null, 'json');
});
```

---

## Google Places Initialization Pattern

```js
function initAutocomplete() {
    const input = document.querySelector('input[name="floorcare_address"]');
    if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) {
        return;
    }

    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['address'],
        componentRestrictions: { country: 'us' }
    });

    autocomplete.addListener('place_changed', function () {
        const place = autocomplete.getPlace();
        if (!place || !place.address_components) return;

        // extract normalized address
    });
}
```

---

## Address Normalization Pattern

```js
function extractAddressComponents(place) {
    const address = {
        street: '',
        city: '',
        state: '',
        zip: ''
    };

    let streetNumber = '';
    let route = '';

    (place.address_components || []).forEach(component => {
        const types = component.types || [];

        if (types.includes('street_number')) {
            streetNumber = component.long_name;
        }

        if (types.includes('route')) {
            route = component.long_name;
        }

        if (types.includes('locality')) {
            address.city = component.long_name;
        }

        if (types.includes('administrative_area_level_1')) {
            address.state = component.short_name;
        }

        if (types.includes('postal_code')) {
            address.zip = component.long_name;
        }
    });

    address.street = [streetNumber, route].filter(Boolean).join(' ');

    return address;
}
```

---

## Error Handling Pattern

### PHP

```php
if (!$result) {
    error_log('HJM FLOORCARE ERROR: Insert failed for booking');
    wp_send_json_error(['message' => 'Database error'], 500);
}
```

### JavaScript

```js
if (!response || !response.success) {
    console.error(response && response.data ? response.data : 'Unknown error');
}
```

---

## Nonce Pattern

### Create

```php
wp_create_nonce('hjm_floorcare_ajax');
```

### Verify

```php
check_ajax_referer('hjm_floorcare_ajax', 'nonce', false);
```

---

## Naming Conventions

- Prefix PHP functions: `hjm_floorcare_`
- Prefix AJAX actions: `floorcare_`
- Prefix JS globals: `floorcare*Context`
- Prefix DB table suffixes: `hjm_floorcare_`
- Build table names with `$wpdb->prefix . '<suffix>'` and never prepend the WordPress prefix manually

---

## Anti-Patterns (Do NOT do)

- Direct SQL with user input
- Missing nonce verification
- Echoing JSON manually
- Inline JavaScript in PHP templates
- Hardcoding API keys
- Duplicating DB logic across files
- Double-prefixing table names (example: `$wpdb->prefix . 'flcwp_*'` when `$table_prefix` is already `flcwp_`)

---

## Agent Expectations

When generating code:

- Reuse patterns in this file
- Follow policy and architectural rules in `AGENTS.md`
- Follow naming conventions strictly
- Maintain security practices
- Keep frontend/backend separation clear
- Align with existing plugin APIs before introducing new names
- Keep `PATTERNS.md` and `AGENTS.md` synchronized when conventions change
