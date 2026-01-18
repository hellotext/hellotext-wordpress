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
	public const API_VERSION = 'v1';
	public const API_ENDPOINT_TRACK = '/v1/track/events';
	public const API_ENDPOINT_PROFILES = '/profiles';
	public const API_ENDPOINT_SESSIONS = '/sessions';
	public const API_ENDPOINT_WEBCHATS = '/v1/wordpress/webchats';
	public const API_ENDPOINT_INTEGRATIONS_WOO = '/integrations/woo';

	// Session & Encryption
	public const ENCRYPTION_METHOD = 'aes-256-cbc';
	public const SESSION_COOKIE_NAME = 'hello_session';

	// WordPress Options
	public const OPTION_BUSINESS_ID = 'hellotext_business_id';
	public const OPTION_ACCESS_TOKEN = 'hellotext_access_token';
	public const OPTION_WEBCHAT_ID = 'hellotext_webchat_id';
	public const OPTION_WEBCHAT_PLACEMENT = 'hellotext_webchat_placement';
	public const OPTION_WEBCHAT_BEHAVIOUR = 'hellotext_webchat_behaviour';

	// User Meta Keys
	public const META_PROFILE_ID = 'hellotext_profile_id';
	public const META_SESSION = 'hellotext_session';

	// Session Keys
	public const SESSION_CART_ITEMS = 'hellotext_cart_items';

	// Event Names
	public const EVENT_ORDER_PLACED = 'order.placed';
	public const EVENT_ORDER_CONFIRMED = 'order.confirmed';
	public const EVENT_ORDER_CANCELLED = 'order.cancelled';
	public const EVENT_ORDER_DELIVERED = 'order.delivered';
	public const EVENT_PRODUCT_PURCHASED = 'product.purchased';
	public const EVENT_PRODUCT_VIEWED = 'product.viewed';
	public const EVENT_CART_ADDED = 'cart.added';
	public const EVENT_CART_REMOVED = 'cart.removed';
	public const EVENT_REFUND_RECEIVED = 'refund.received';
	public const EVENT_COUPON_REDEEMED = 'coupon.redeemed';

	// Logging
	public const LOG_PREFIX = '[Hellotext]';
}
