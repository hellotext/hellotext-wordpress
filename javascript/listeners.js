var events = {
  updated_cart_totals: checkout => {
    console.log(checkout);
  },
}

Object.keys(events).forEach(event => {
  jQuery(document).on(event, events[event]);
})
