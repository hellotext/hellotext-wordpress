export default async function couponEvent(coupon) {
  const code = document.querySelector('.checkout_coupon #coupon_code').value

  Hellotext.track('coupon.redeemed', { code })
}
