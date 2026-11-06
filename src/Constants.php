<?php

namespace Hellotext;

/**
 * Constants
 *
 * Centralized constants used across the Hellotext plugin.
 *
 * @package Hellotext
 */
class Constants {
    // API Configuration
    public const API_URL = 'https://api.hellotext.com';
    public const API_ENDPOINT_TRACK = '/v1/track/events';
    public const API_ENDPOINT_PROFILES = '/v1/profiles';
    public const API_ENDPOINT_SESSIONS = '/v1/sessions';
    public const API_ENDPOINT_INTEGRATIONS_WOO = '/integrations/woo';

    // Hellotext.js, unversioned so stores always load the latest release
    public const SDK_URL = 'https://cdn.jsdelivr.net/npm/@hellotext/hellotext/dist/hellotext.min.js';

    // Session & Encryption
    public const ENCRYPTION_METHOD = 'aes-256-cbc';
    public const SESSION_COOKIE_NAME = 'hello_session';

    // WordPress Options
    public const OPTION_BUSINESS_ID = 'hellotext_business_id';
    public const OPTION_ACCESS_TOKEN = 'hellotext_access_token';

    // Every option the plugin has stored, including the webchat options of
    // earlier versions, removed on uninstall.
    public const OPTIONS = [
        self::OPTION_BUSINESS_ID,
        self::OPTION_ACCESS_TOKEN,
        'hellotext_webchat_id',
        'hellotext_webchat_placement',
        'hellotext_webchat_behaviour',
    ];

    // WooCommerce API key
    public const API_KEY_DESCRIPTION = 'Hellotext';

    // User Meta Keys
    public const META_PROFILE_ID = 'hellotext_profile_id';
    public const META_SESSION = 'hellotext_session';

    // Order Meta Keys
    public const META_ORDER_PLACED = 'hellotext_order_placed';
    public const ORDER_PLACED_PENDING = 'pending';
    public const ORDER_PLACED_TRACKED = 'tracked';

    // WooCommerce Session Keys
    public const SESSION_CART_ITEMS = 'hellotext_cart_items';
    public const SESSION_CHECKOUT_STARTED = 'hellotext_checkout_started';

    // Event Names
    public const EVENT_ORDER_PLACED = 'order.placed';
    public const EVENT_ORDER_CONFIRMED = 'order.confirmed';
    public const EVENT_ORDER_CANCELLED = 'order.cancelled';
    public const EVENT_ORDER_DELIVERED = 'order.delivered';
    public const EVENT_PRODUCT_PURCHASED = 'product.purchased';
    public const EVENT_PRODUCT_VIEWED = 'product.viewed';
    public const EVENT_CART_ADDED = 'cart.added';
    public const EVENT_CART_REMOVED = 'cart.removed';
    public const EVENT_CART_VIEWED = 'cart.viewed';
    public const EVENT_CHECKOUT_STARTED = 'checkout.started';
    public const EVENT_REFUND_RECEIVED = 'refund.received';
    public const EVENT_COUPON_REDEEMED = 'coupon.redeemed';

    // Logging
    public const LOG_PREFIX = '[Hellotext]';
}
