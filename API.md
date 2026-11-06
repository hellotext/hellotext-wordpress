# Hellotext WordPress Plugin - API Documentation

## Overview

This document provides comprehensive API documentation for the Hellotext WordPress plugin. The plugin integrates WooCommerce with Hellotext's customer engagement platform, tracking customer activities and synchronizing profiles.

## Table of Contents

- [Core Concepts](#core-concepts)
- [HTTP Client](#http-client)
- [Event Tracking](#event-tracking)
- [Adapters](#adapters)
- [Services](#services)
- [Constants Reference](#constants-reference)
- [WordPress Hooks](#wordpress-hooks)

## Core Concepts

### Architecture

The plugin follows a clean architecture pattern with distinct layers:

- **Api/** - HTTP clients for external communication
- **Adapters/** - Transform WooCommerce data to Hellotext payloads
- **Events/** - Event handlers for WooCommerce hooks
- **Services/** - Business logic for profile and session management
- **Misc/** - WordPress integration (settings, scripts)

### Session Management

The plugin uses a cookie-based session system (`hello_session`) to track anonymous users and associate their activities with Hellotext profiles.

## HTTP Client

### `Hellotext\Api\Client`

The main HTTP client for communicating with the Hellotext API.

#### Static Methods

##### `request(string $method, string $path, array $data = []): array`

Makes an HTTP request to the Hellotext API.

**Parameters:**

- `$method` - HTTP method (GET, POST, PATCH, PUT, DELETE)
- `$path` - API endpoint path
- `$data` - Request payload

**Returns:**

```php
[
    'request' => [
        'method' => string,
        'path' => string,
        'data' => array
    ],
    'status' => int,      // HTTP status code
    'body' => array|null  // Decoded JSON response
]
```

**Example:**

```php
use Hellotext\Api\Client;
use Hellotext\Constants;

$response = Client::post(Constants::API_ENDPOINT_PROFILES, [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com'
]);
```

##### Convenience Methods

- `get(string $path, ?array $data = null): array`
- `post(string $path, ?array $data = null): array`
- `patch(string $path, ?array $data = null): array`
- `put(string $path, ?array $data = null): array`
- `delete(string $path, ?array $data = null): array`

##### `api_url(): string`

Returns the Hellotext API URL: `https://api.hellotext.com`, or the `HELLOTEXT_API_URL` constant when it is defined.

## Event Tracking

### `Hellotext\Api\Event`

Tracks customer activities and sends them to Hellotext.

#### Constructor

```php
public function __construct(?string $session = null)
```

Creates a new event tracker for the given session. Storefront events pass `Session::current()`; order and refund events pass `Session::for_order($order)`, because they also run from wp-admin and payment callbacks, where the browser cookie belongs to someone else.

#### Methods

##### `track(string $action, array $payload): void`

Tracks an event with the given action and payload.

**Parameters:**

- `$action` - Event action name (use constants from `Constants::EVENT_*`)
- `$payload` - Event data

**Example:**

```php
use Hellotext\Api\Event;
use Hellotext\Constants;

$event = new Event();
$event->track(Constants::EVENT_PRODUCT_VIEWED, [
    'object_parameters' => [
        'reference' => '123',
        'name' => 'Product Name',
        'price' => ['amount' => 2999, 'currency' => 'USD']
    ]
]);
```

## Adapters

Adapters transform WooCommerce objects into Hellotext-compatible payloads.

### `Hellotext\Adapters\ProductAdapter`

Transforms WooCommerce products.

#### Constructor

```php
public function __construct(int|\WC_Product $product)
```

Accepts either a product ID or a `WC_Product` instance.

#### Methods

##### `get(): array`

Returns the adapted product payload.

**Returns:**

```php
[
    'reference' => int,          // Product ID
    'source' => 'woo',
    'name' => string,
    'categories' => string[],
    'price' => [
        'amount' => int,         // Price in cents
        'currency' => string
    ],
    'tags' => string[],
    'image_url' => string,
    'url' => string             // Product permalink
]
```

**Example:**

```php
use Hellotext\Adapters\ProductAdapter;

$adapter = new ProductAdapter($product_id);
$payload = $adapter->get();
```

### `Hellotext\Adapters\OrderAdapter`

Transforms WooCommerce orders.

#### Constructor

```php
public function __construct(\WC_Order $order)
```

#### Methods

##### `get(): array`

Returns the adapted order payload.

**Returns:**

```php
[
    'reference' => int,          // Order ID
    'source' => 'woo',
    'status' => string,          // Order status
    'subtotal' => ['amount' => int, 'currency' => string],
    'total' => ['amount' => int, 'currency' => string],
    'tax' => ['amount' => int, 'currency' => string],
    'shipping' => ['amount' => int, 'currency' => string],
    'discount' => ['amount' => int, 'currency' => string],
    'items' => array[],          // Order items
    'url' => string
]
```

### `Hellotext\Adapters\RefundAdapter`

Transforms WooCommerce refunds.

#### Constructor

```php
public function __construct(\WC_Order_Refund $refund)
```

#### Methods

##### `get(): array`

Returns the adapted refund payload.

### `Hellotext\Adapters\PriceAdapter`

Converts prices to Hellotext format (cents-based).

#### Constructor

```php
public function __construct(float|string|null $price)
```

#### Methods

##### `get(): array`

Returns price in cents with currency.

**Returns:**

```php
[
    'amount' => int,    // Price in cents
    'currency' => string // e.g., 'USD'
]
```

## Services

### `Hellotext\Services\CreateProfile`

Manages Hellotext profile creation and association.

#### Constructor

```php
public function __construct(?int $user_id, array $billing = [])
```

**Parameters:**

- `$user_id` - WordPress user ID (null for guest checkout)
- `$billing` - Billing data from checkout

#### Methods

##### `process(): void`

Executes the profile creation/association flow:

1. Checks if profile exists
2. Creates new profile if needed
3. Associates profile with session
4. Updates session metadata

**Example:**

```php
use Hellotext\Services\CreateProfile;

$service = new CreateProfile($user->ID);
$service->process();

// For guest checkout with billing data
$service = new CreateProfile(null, [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'jane@example.com',
    'phone' => '+1234567890'
]);
$service->process();
```

### `Hellotext\Services\Session`

Reads the `hello_session` cookie that Hellotext.js sets, and encrypts the copy stored on orders.

#### Static Methods

##### `current(): ?string`

The visitor's Hellotext.js session, or `null` when the cookie is missing.

##### `for_order(WC_Order $order): ?string`

The session stored on the order at checkout, or `null` when there is none.

##### `encrypt(?string $session): string`

Encrypts a session string using AES-256-CBC.

##### `decrypt(mixed $encrypted_session): string|false`

Decrypts an encrypted session string. Returns `false` for empty or malformed values.

**Example:**

```php
use Hellotext\Services\Session;

$order->update_meta_data('hellotext_session', Session::encrypt(Session::current()));
$order->save_meta_data();

$session = Session::for_order($order);
```

## Constants Reference

### `Hellotext\Constants`

All plugin constants are centralized in this class.

#### API Configuration

```php
Constants::API_URL                     // 'https://api.hellotext.com'
Constants::API_ENDPOINT_TRACK          // '/v1/track/events'
Constants::API_ENDPOINT_PROFILES       // '/v1/profiles'
Constants::API_ENDPOINT_SESSIONS       // '/v1/sessions'
Constants::API_ENDPOINT_INTEGRATIONS_WOO // '/integrations/woo'
Constants::SDK_URL                     // Hellotext.js on jsDelivr, unversioned
```

#### Session & Encryption

```php
Constants::ENCRYPTION_METHOD           // 'aes-256-cbc'
Constants::SESSION_COOKIE_NAME        // 'hello_session'
```

#### WordPress Options

```php
Constants::OPTION_BUSINESS_ID         // 'hellotext_business_id'
Constants::OPTION_ACCESS_TOKEN        // 'hellotext_access_token'
Constants::OPTIONS                    // every option above plus the webchat options of earlier versions, removed on uninstall
```

#### Meta Keys

```php
Constants::META_PROFILE_ID            // 'hellotext_profile_id' (user)
Constants::META_SESSION               // 'hellotext_session' (user and order)
Constants::META_ORDER_PLACED          // 'hellotext_order_placed' (order): 'pending' after checkout, 'tracked' once order.placed is sent
```

#### Event Names

```php
Constants::EVENT_ORDER_PLACED         // 'order.placed'
Constants::EVENT_ORDER_CONFIRMED      // 'order.confirmed'
Constants::EVENT_ORDER_CANCELLED      // 'order.cancelled'
Constants::EVENT_ORDER_DELIVERED      // 'order.delivered'
Constants::EVENT_PRODUCT_PURCHASED    // 'product.purchased'
Constants::EVENT_PRODUCT_VIEWED       // 'product.viewed'
Constants::EVENT_CART_ADDED           // 'cart.added'
Constants::EVENT_CART_REMOVED         // 'cart.removed'
Constants::EVENT_REFUND_RECEIVED      // 'refund.received'
Constants::EVENT_COUPON_REDEEMED      // 'coupon.redeemed'
```

## WordPress Hooks

The plugin registers these WooCommerce and WordPress hooks:

| Hook                                          | Handler                          | Purpose                                                                                                                          |
| --------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `woocommerce_after_single_product`            | `hellotext_product_viewed`       | Track `product.viewed`                                                                                                           |
| `woocommerce_after_cart`                      | `hellotext_trigger_cart_updated` | Schedule a cart comparison at shutdown                                                                                           |
| `woocommerce_add_to_cart`                     | `hellotext_trigger_cart_updated` | Schedule a cart comparison at shutdown                                                                                           |
| `woocommerce_cart_item_removed`               | `hellotext_trigger_cart_updated` | Schedule a cart comparison at shutdown                                                                                           |
| `woocommerce_after_cart_item_quantity_update` | `hellotext_trigger_cart_updated` | Schedule a cart comparison at shutdown                                                                                           |
| `shutdown`                                    | `hellotext_cart_updated`         | Track `cart.added` with the product's whole quantity in the cart, which Hellotext sets the line to, and `cart.removed` with the quantity removed, after WooCommerce recalculates totals. A Webchat card's addition carries its message as `source` |
| `woocommerce_cart_emptied`                    | `hellotext_reset_cart_snapshot`  | Forget the cart snapshot and the tracked checkout so purchased items are not reported as removed and the next checkout is reported |
| `template_redirect`                           | `hellotext_cart_viewed`          | Track `cart.viewed` on the cart page with the whole cart                                                                         |
| `template_redirect`                           | `hellotext_checkout_started`     | Track `checkout.started` on the checkout page, once per cart; not on the thank-you or payment pages                              |
| `template_redirect`                           | `hellotext_checkout_link`        | `?hellotext_checkout=1&products=id:quantity,…`, the checkout link Hellotext sends stores older than WooCommerce 10.0: replace the cart with the products and open the checkout with the link's other parameters, or the page of a product that needs options |
| `woocommerce_applied_coupon`                  | `hellotext_coupon_redeemed`      | Track valid `coupon.redeemed` events                                                                                             |
| `woocommerce_checkout_order_processed`        | `hellotext_checkout_order_processed` | Classic checkout: store the encrypted session on the order and create the customer's profile                                 |
| `woocommerce_store_api_checkout_order_processed` | `hellotext_capture_checkout`  | Checkout block: store the encrypted session on the order and create the customer's profile                                       |
| `woocommerce_thankyou`                        | `hellotext_thankyou`             | Track `order.placed` once the order is paid or on hold                                                                           |
| `woocommerce_order_status_changed`            | `hellotext_order_status_changed` | Track `order.placed` if still pending (block themes, customers who never return from payment), then `order.confirmed`, `order.cancelled`, `order.delivered` |
| `woocommerce_order_refunded`                  | `hellotext_refund_created`       | Track `refund.received`                                                                                                          |
| `user_register`                               | `hellotext_user_registered`      | Track customer registration/profile flow                                                                                         |
| `add_option_*` / `update_option_*` (Business ID, Access Token) | `hellotext_schedule_connect`, `hellotext_business_id_updated` | Connect the store once both settings are saved, sending the WooCommerce version; remove the previous business's integration first when the Business ID changes. The previous API key keeps working until Hellotext accepts the new one |
| `pre_update_option_hellotext_access_token`    | `hellotext_retry_failed_connection` | Retry a failed connection when the settings are saved again, even unchanged; the settings page shows the failure |
| `admin_init`                                  | `hellotext_settings_init`        | Register plugin settings                                                                                                         |
| `wp_enqueue_scripts`                          | `hellotext_enqueue_script`       | Load the latest Hellotext.js and `assets/storefront.js` on the storefront, with its settings in `window.hellotextWoo` |

## WooCommerce Compatibility

Compatibility posture as of 2026-06-11. See [WooCommerce Compatibility and API Audit](docs/WOOCOMMERCE-AUDIT.md) for the full hook/API audit, HPOS assessment, compatibility matrix, and release recommendations.

| Area                           | Status                       | Notes                                                                                                                              |
| ------------------------------ | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| WooCommerce order metadata     | HPOS-aligned metadata access | Uses `WC_Order` metadata CRUD for Hellotext session metadata.                                                                      |
| Order status and refund events | HPOS-aligned metadata access | Reads session metadata from the order object instead of `get_post_meta()`.                                                         |
| Product, cart, coupon events   | Public hook/API usage        | Uses WooCommerce hooks and objects, not order storage internals.                                                                   |
| Automated tests                | Mock-backed unit coverage    | Event tests cover outbound payloads without real WordPress/WooCommerce runtime or HPOS datastore coverage.                         |
| Runtime verification           | Required before declaration  | Test with HPOS enabled and disabled in a real WooCommerce site before declaring formal compatibility in the plugin header/runtime. |

Do not add a formal HPOS compatibility declaration until the release candidate has passed runtime checks on WooCommerce with HPOS enabled and disabled.

### Actions

#### `hellotext_create_profile`

Triggers profile creation/association. Fire it only from requests the customer makes, since it links the profile to the browser's Hellotext.js session.

**Parameters:**

- `$payload` - User ID (int), billing data (array), or nothing for the current user

**Usage:**

```php
// For logged-in user
do_action('hellotext_create_profile', $user_id);

// For guest with billing data
do_action('hellotext_create_profile', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890'
]);
```

### WooCommerce Integration Hooks

The plugin automatically hooks into these WooCommerce actions:

- `woocommerce_checkout_order_processed` / `woocommerce_store_api_checkout_order_processed` - Capture the checkout session
- `woocommerce_thankyou` - Track order placement
- `woocommerce_order_status_changed` - Track order status updates
- `woocommerce_order_refunded` - Track refunds
- `woocommerce_add_to_cart` - Track cart additions
- `woocommerce_cart_item_removed` - Track cart removals
- `woocommerce_applied_coupon` - Track coupon usage
- `template_redirect` - Track the cart page and the checkout
- `wp_enqueue_scripts` - Load Hellotext.js. Webchat, popups and other widgets are configured from Hellotext

### Storefront script

`assets/storefront.js` starts Hellotext.js with the settings the plugin prints in `window.hellotextWoo`. After `Hellotext.initialize()`, it:

1. Sets `Hellotext.page.section`, so every event of the page reports it, Webchat messages included.
2. Tracks `page.viewed`, which Hellotext.js does not do on its own, except on product pages (`product.viewed`) and the checkout (`checkout.started`).
3. Calls `Hellotext.alert.show(section)`. Hellotext shows the alert only for the sections enabled in the Smart Alert playbook.

It also listens for the `cart.added` event that the Webchat's product cards dispatch, and adds the card's product to the WooCommerce cart through `?wc-ajax=add_to_cart`, with the message in `hellotext_source` so the plugin credits the addition to it. It then refreshes the mini cart with `added_to_cart`, as WooCommerce's own buttons do. Products that need options open their page, and the store's "Redirect to the cart page after successful addition" setting is followed.

### Page sections

| Page                                              | Section              |
| ------------------------------------------------- | -------------------- |
| Front page (also when the shop is the front page) | `homepage`           |
| Shop, product categories, tags and attributes     | `product_collection` |
| Product                                           | `product_details`    |
| Anything else (cart, checkout, account, blog)     | none                 |

The Smart Alert subscribes visitors to Push, which needs a service worker on the store's origin. The plugin ships `assets/hellotext-service-worker.js`, the worker from the Hellotext.js Push guide, and passes its path as `push.serviceWorkerUrl`. It registers under the plugin's folder, so it never replaces a worker the store already runs at the site root.

## Testing

For testing information, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Configuration Constants

Define `HELLOTEXT_API_URL` in `wp-config.php` to point the plugin at another Hellotext environment:

```php
define('HELLOTEXT_API_URL', 'https://api-dev.hellotext.com');
```

## Error Handling

Most methods that interact with external APIs will throw exceptions on failure. Always wrap critical API calls in try-catch blocks:

```php
try {
    $adapter = new ProductAdapter($product_id);
    $payload = $adapter->get();
} catch (\Exception $e) {
    error_log('Hellotext: Failed to adapt product - ' . $e->getMessage());
}
```

## Security

### Authentication

All API requests include an `Authorization: Bearer` header with either:

- **Business ID** for event tracking
- **Access Token** for profile/session management

### Session Encryption

Sessions are encrypted using AES-256-CBC before storage in the database. The encryption key is derived from WordPress security keys.

### Data Sanitization

All user input is sanitized using WordPress functions (`sanitize_text_field()`, etc.) before processing.

## License

This plugin is licensed under GPL v2. See [LICENSE](LICENSE) for details.
