import { couponEvent } from './events/index.js'

const events = {
  // Coupen Events
  applied_coupon_in_checkout: couponEvent,
  applied_coupon: couponEvent,
}


Object.keys(events).forEach(event => {
  jQuery(document).on(event, events[event])
})

