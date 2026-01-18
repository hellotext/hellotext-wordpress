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

##### `with_sufix(string $sufix = ''): self`

Creates a client instance with a custom API suffix. Useful for testing.

**Example:**
```php
$client = Client::with_sufix('/v2');
```

## Event Tracking

### `Hellotext\Api\Event`

Tracks customer activities and sends them to Hellotext.

#### Constructor

```php
public function __construct(?string $session = null)
```

Creates a new event tracker. If no session is provided, it attempts to retrieve it from the `hello_session` cookie.

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

Manages session encryption and cookies.

#### Static Methods

##### `create(): string`

Creates a new session identifier.

**Returns:** UUID session string

##### `encrypt(string $session): string`

Encrypts a session string using AES-256-CBC.

##### `decrypt(string $encrypted_session): string`

Decrypts an encrypted session string.

##### `set_cookie(string $session): void`

Sets the `hello_session` cookie with proper expiration and security flags.

**Example:**
```php
use Hellotext\Services\Session;

$session = Session::create();
Session::set_cookie($session);

// Later, encrypt for storage
$encrypted = Session::encrypt($session);
add_post_meta($order_id, 'hellotext_session', $encrypted);
```

## Constants Reference

### `Hellotext\Constants`

All plugin constants are centralized in this class.

#### API Configuration

```php
Constants::API_VERSION                  // 'v1'
Constants::API_ENDPOINT_TRACK          // '/v1/track/events'
Constants::API_ENDPOINT_PROFILES       // '/profiles'
Constants::API_ENDPOINT_SESSIONS       // '/sessions'
Constants::API_ENDPOINT_WEBCHATS       // '/v1/wordpress/webchats'
Constants::API_ENDPOINT_INTEGRATIONS_WOO // '/integrations/woo'
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
Constants::OPTION_WEBCHAT_ID          // 'hellotext_webchat_id'
Constants::OPTION_WEBCHAT_PLACEMENT   // 'hellotext_webchat_placement'
Constants::OPTION_WEBCHAT_BEHAVIOUR   // 'hellotext_webchat_behaviour'
```

#### User Meta Keys

```php
Constants::META_PROFILE_ID            // 'hellotext_profile_id'
Constants::META_SESSION               // 'hellotext_session'
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

### Actions

#### `hellotext_create_profile`

Triggers profile creation/association.

**Parameters:**
- `$payload` - Either user ID (int) or billing data (array)

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

- `woocommerce_after_order_details` - Track order placement
- `woocommerce_order_status_changed` - Track order status updates
- `woocommerce_order_refunded` - Track refunds
- `woocommerce_add_to_cart` - Track cart additions
- `woocommerce_cart_item_removed` - Track cart removals
- `woocommerce_applied_coupon` - Track coupon usage
- `wp_footer` - Inject tracking scripts and webchat

## Testing

For testing information, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Environment Variables

The plugin respects the following environment variables:

- `APP_ENV` - Environment mode: `production`, `development`, or `test`
- `HELLOTEXT_API_URL` - Override API URL (development only)

**Example (.htaccess):**
```apache
SetEnv APP_ENV development
SetEnv HELLOTEXT_API_URL https://api-dev.hellotext.com
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
