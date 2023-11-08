export default async function productEvent(coupon, event = 'viewed') {
  const id = document.querySelector('form.cart button[name="add-to-cart"]').value
  const response = await fetch(`/wp-json/hellotext/v1/product/${id}`)
  const product = await response.json()

  Hellotext.track(`product.${event}`, { product_parameters: product })
}

