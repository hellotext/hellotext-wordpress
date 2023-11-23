import {
  cartEvent,
  couponEvent,
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
  // - order.confirmed => hellotext/events/order_confirmed.php
  // - order.cancelled => hellotext/events/order_cancelled.php
}


Object.keys(events).forEach(event => {
  jQuery(document).on(event, events[event])
})

