export default async function couponEvent(coupon) {
  const code = document.querySelector('#coupon_code').value
  const response = await fetch(`/wp-json/hellotext/v1/coupon/${code}`)
  const { valid } = await response.json()


  if (valid) {
    Hellotext.track('coupon.redeemed', { code })
  }
}
