# WooCommerce Compatibility and API Audit

Last reviewed: 2026-06-11

This audit covers the WooCommerce and WordPress integration points used by the Hellotext WooCommerce plugin. It is intentionally maintenance-focused: it documents current hooks, validates order access against current WooCommerce HPOS guidance, and records practical release risks without changing Hellotext API contracts.

## References

- WooCommerce APIs overview: <https://developer.woocommerce.com/docs/apis/>
- WooCommerce block hooks reference: <https://developer.woocommerce.com/docs/block-development/reference/hooks/>
- WooCommerce HPOS extension recipe book: <https://developer.woocommerce.com/docs/features/high-performance-order-storage/recipe-book>
- WooCommerce Code Reference: <https://woocommerce.github.io/code-reference/>
- WordPress Plugin Handbook hooks and activation/deactivation APIs: <https://developer.wordpress.org/plugins/>

## Summary

The plugin uses WooCommerce PHP hooks and WooCommerce objects for the customer activity it tracks. Order session metadata access follows HPOS guidance where the plugin uses `wc_get_order()`, `$order->get_meta()`, `$order->update_meta_data()`, and `$order->save()` instead of direct post meta functions.

This is not a full runtime compatibility claim. The current tests use mocks and do not prove behavior against a real WooCommerce HPOS datastore, Cart/Checkout blocks, or block themes.

Do not declare HPOS compatibility yet. Add the declaration only after a manual runtime smoke test passes with WooCommerce HPOS enabled and disabled in a real WordPress/WooCommerce install.

## Hook and API Matrix

| Area | Hook/API | Handler | Current status | Notes |
| --- | --- | --- | --- | --- |
| Product views | `woocommerce_after_single_product` | `hellotext_product_viewed()` | Classic template coverage | This is a classic template hook. Confirm behavior with block themes before claiming full block-theme coverage. |
| Cart updates | `woocommerce_add_to_cart` | `hellotext_trigger_cart_updated()` | Appropriate | Mutation hook is still a valid WooCommerce cart hook. Tests cover event creation with mocks. |
| Cart removals | `woocommerce_cart_item_removed` | `hellotext_trigger_cart_updated()` | Appropriate | Tests cover `cart.removed` payloads. |
| Cart quantity changes | `woocommerce_after_cart_item_quantity_update` | `hellotext_trigger_cart_updated()` | Appropriate | Tests cover quantity increases and no-op unchanged carts. |
| Cart page diff check | `woocommerce_after_cart` | `hellotext_trigger_cart_updated()` | Works for classic cart template | This hook does not cover all Cart block render paths. Cart block changes still go through the cart mutation hooks above. |
| Cart comparison | `shutdown` | `hellotext_cart_updated()` | Appropriate | The cart hooks schedule one comparison at the end of the request, after WooCommerce recalculates totals. The snapshot lives in `WC()->session`, not a PHP session. |
| Coupon redemption | `woocommerce_applied_coupon` | `hellotext_coupon_redeemed()` | Appropriate | Handler validates the coupon before sending `coupon.redeemed`; tests cover valid and invalid coupons. |
| Checkout capture | `woocommerce_checkout_order_processed`, `woocommerce_store_api_checkout_order_processed` | `hellotext_capture_checkout()` | Appropriate | Classic and block checkout store the encrypted session and a `pending` placement flag on the order, in the customer's request. |
| Order placement | `woocommerce_thankyou` and `woocommerce_order_status_changed` | `hellotext_order_placed()` | Appropriate | Sent once per order, when it is paid or on hold. The status change covers block themes, whose order confirmation page fires no classic hooks, and customers who never return from the payment page. |
| Order status changes | `woocommerce_order_status_changed` | `hellotext_order_status_changed()` | Appropriate | Handler maps `processing`, `cancelled`, and `completed` to Hellotext events with the session stored on the order, never the shop manager's cookie. |
| Refunds | `woocommerce_order_refunded` | `hellotext_refund_created()` | Appropriate | Handler loads orders/refunds with WooCommerce APIs and reads session meta with `$order->get_meta()`. |
| User registration | `user_register` | `hellotext_user_registered()` | Appropriate WordPress hook | Tests cover profile creation flow with mocked HTTP. |
| Profile creation | `hellotext_create_profile` | Closure in `CreateProfile.php` | Internal plugin hook | Used by event flows to associate logged-in users/sessions. |
| Plugin activation | `register_activation_hook()` | `hellotext_activate()` | Appropriate WordPress API | Connects the store when the settings are already saved. |
| Plugin deactivation | `register_deactivation_hook()` | `hellotext_deactivate()` | Appropriate WordPress API | Removes the integration from Hellotext and revokes its WooCommerce API key. |
| Plugin uninstall | `register_uninstall_hook()` | `hellotext_uninstall()` | Appropriate WordPress API | Cleans Hellotext options, including the webchat options of earlier versions, and WooCommerce API keys. |
| Settings | WordPress Settings API | `src/Misc/Settings.php` | Appropriate | Stores the Business ID and access token. Webchat and other widgets are configured in Hellotext. |
| Hellotext.js | `wp_enqueue_scripts` | `hellotext_enqueue_script()` | Appropriate WordPress APIs | Loads the unversioned jsDelivr build on the storefront only. Verify rendered script manually in a real WordPress page during release smoke testing. |
| WooCommerce API keys | Direct `$wpdb` against `{$prefix}woocommerce_api_keys` | `src/Events/AppInstalled.php`, `hellotext.php` uninstall | Acceptable exception | WooCommerce API keys are not order data and are not part of HPOS order storage. No WooCommerce CRUD replacement is required for this table access. |

## HPOS Assessment

WooCommerce HPOS changes where order data is stored. The HPOS recipe book recommends avoiding direct WordPress post/postmeta APIs for order data and using WooCommerce CRUD APIs instead.

The plugin's order session metadata paths are aligned with that guidance:

- `src/Adapters/OrderAdapter.php` loads numeric orders with `wc_get_order()`.
- `src/Events/OrderPlaced.php` stores the Hellotext session and placement flag with `$order->update_meta_data(...)` and `$order->save_meta_data()`.
- `src/Events/OrderStatus.php` and `src/Events/RefundReceived.php` read the stored session through `Session::for_order()`, which uses `$order->get_meta(Constants::META_SESSION, true)`.
- The plugin does not query `shop_order` posts or use direct post meta functions for order session metadata.

The test suite uses WordPress/WooCommerce mocks, so it validates code behavior and request shape but not a real HPOS datastore. Because of that, the formal declaration is deferred.

## Runtime Gaps To Smoke Test

- Classic product page and block-theme product page behavior.
- Classic cart page and Cart block behavior.
- Classic checkout and Checkout block order placement behavior.
- Duplicate `order.placed` prevention when an order details page is revisited.
- HPOS enabled and disabled order placement, status change, and refund flows.

## Deferred HPOS Declaration

After a real WooCommerce smoke test passes with HPOS enabled and disabled, add this to the main plugin file:

```php
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
```

Do not add this declaration until manual runtime testing confirms product view, cart, coupon, checkout, status transition, refund, settings, deactivation, and webchat behavior in a real store with HPOS both enabled and disabled.

## Compatibility Matrix

| Layer | Declared | CI-tested | Stub-tested | Assumed or manual-only |
| --- | --- | --- | --- | --- |
| PHP | README requires PHP 8.2+; Composer platform is PHP 8.2.12 | GitHub Actions runs Pest on PHP 8.2, 8.3, 8.4, and 8.5; format check on PHP 8.2 in CI | Not applicable | Local contributor machines may run newer PHP; PHP CS Fixer warns when run outside project platform. |
| WordPress | README requires WordPress 5.0+ | No full WordPress runtime in CI | `php-stubs/wordpress-stubs` is locked to 6.9.x after dependency refresh | Runtime compatibility with each supported WordPress version is manual-only. |
| WooCommerce | README requires WooCommerce 5.0+ | No full WooCommerce runtime in CI | `php-stubs/woocommerce-stubs` is locked to 10.x after dependency refresh | HPOS, Cart/Checkout blocks, and block themes require manual smoke testing. |
| Hellotext API | Existing plugin endpoints and payload contracts | HTTP calls are intercepted in tests | Not applicable | Real API authentication and dashboard ingestion require staging/production smoke testing. |
| Release package | `.github/workflows/release.yml` builds tag-triggered zip | Release workflow runs only on `v*` tags | Not applicable | Zip contents and installability must be checked after each tagged release. |

## Recommendations

1. Keep the HPOS declaration deferred until manual smoke testing passes with HPOS on and off.
2. Done: `order.placed` is guarded by the `hellotext_order_placed` order flag.
3. Done: `woocommerce_after_order_details` was replaced with the classic and Store API checkout hooks, `woocommerce_thankyou`, and the status change fallback.
4. Document classic-template limitations for `woocommerce_after_single_product`, `woocommerce_after_cart`, and any block-theme gaps in release notes until runtime coverage is confirmed.
5. Done: the cart snapshot moved from `$_SESSION` to `WC()->session`, and the plugin no longer starts a PHP session.
6. Continue using WooCommerce CRUD methods for all future order/refund metadata reads and writes.
