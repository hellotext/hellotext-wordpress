import {
  cartEvent,
  couponEvent,
  productEvent,
} from './events/index.js'

const events = {
  // Cart Events
  added_to_cart: cartEvent,
  cart_page_refreshed: cartEvent,
  removed_from_cart: cartEvent,
  updated_cart_totals: cartEvent,
  updated_cart_totals: cartEvent,
  updated_checkout: cartEvent,
  wc_cart_emptied: cartEvent,

  // Coupen Events
  applied_coupon_in_checkout: couponEvent,
  applied_coupon: couponEvent,


  // Order Events
  // - order.placed    => hellotext/events/order_placed.php
  // - order.confirmed
  // - order.cancelled

  // Product Events
  // - product.purchased => hellotext/events/order_placed.php (We add this event here)
  product_viewed: e => productEvent(e, 'viewed'),
}


Object.keys(events).forEach(event => {
  jQuery(document).on(event, events[event])
})

// Custom Events
if (document.querySelector('.single-product')) {
  jQuery(document).trigger('product_viewed')
}
