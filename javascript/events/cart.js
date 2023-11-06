export default async function cartEvent() {
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

  if (changes.added.length > 0) {
    Hellotext.track('cart.added', { products: changes.added })
  }

  if (changes.removed.length > 0) {
    Hellotext.track('cart.removed', { products: changes.removed })
  }

  sessionStorage.setItem('hellotext-cart', JSON.stringify(cart))
}
