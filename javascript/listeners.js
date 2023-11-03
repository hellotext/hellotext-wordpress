const events = {
  updated_checkout: cartUpdated,
  updated_cart_totals: cartUpdated,
  wc_cart_emptied: cartUpdated,
  updated_cart_totals: cartUpdated,
  added_to_cart: cartUpdated,
  removed_from_cart: cartUpdated,
  cart_page_refreshed: cartUpdated
};

async function cartUpdated() {
  const response = await fetch('/wp-json/hellotext/v1/cart')
  const cart = await response.json()
  const previousCart = JSON.parse(sessionStorage.getItem('hellotext-cart'))
  const changes = {
    added: [],
    removed: []
  }

  if (previousCart) {
    cart.forEach(item => {
      const previousItem = previousCart.find(previousItem => previousItem.id === item.id)

      if (!previousItem || previousItem.quantity < item.quantity) {
        changes.added.push(item)
      }
    })

    previousCart.forEach(item => {
      const currentItem = cart.find(currentItem => currentItem.id === item.id)

      if (!currentItem || currentItem.quantity < item.quantity) {
        changes.removed.push(currentItem)
      }
    })
  }

  changes.added.forEach(item => { Hellotext.track('cart.added', item) })
  changes.removed.forEach(item => { Hellotext.track('cart.removed', item) })

  console.log(changes)

  sessionStorage.setItem('hellotext-cart', JSON.stringify(cart))
}

Object.keys(events).forEach(event => {
  jQuery(document).on(event, events[event]);
})
