/**
 * Starts Hellotext.js on the storefront with the settings the plugin prints
 * in window.hellotextWoo.
 */
;(function () {
  var config = window.hellotextWoo
  var Hellotext = window.Hellotext

  if (!config || !Hellotext) return

  // The Webchat's product cards only announce the product. Adding it to the
  // WooCommerce cart is the store's part.
  if (typeof Hellotext.on === 'function') {
    Hellotext.on('cart.added', function (event) {
      addToCart(event).catch(function (error) {
        console.warn('Hellotext could not add the product to the cart:', error)
      })
    })
  }

  Hellotext.initialize(config.businessId, config.options)
    .then(function () {
      // Initializing builds a new page, so its section is set afterwards, and
      // before page.viewed so the event reports it.
      if (Hellotext.page) Hellotext.page.section = config.section

      // Hellotext.js does not track page views itself. Product and checkout
      // pages report their own events instead.
      if (config.trackPageView) Hellotext.track('page.viewed')

      if (config.section && Hellotext.alert) Hellotext.alert.show(config.section)
    })
    .catch(function (error) {
      console.warn('Hellotext initialization failed:', error)
    })

  async function addToCart(event) {
    var items = (event && event.object_parameters && event.object_parameters.items) || []
    var added = false

    for (var item of items) {
      // Cards from another store's catalog carry that store's references.
      if (!item.reference || (item.source && item.source !== 'woo')) continue

      var response = await fetch(config.addToCartUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          product_id: item.reference,
          quantity: item.quantity || 1,
          // The plugin reports the message that led to the addition.
          hellotext_source: JSON.stringify((event && event.source) || {}),
        }),
      })
      var result = await response.json()

      if (!result || result.error) {
        // Like WooCommerce's own buttons: products that need options picked,
        // or are out of stock, open their page.
        if (result && result.product_url) window.location.href = result.product_url
        return
      }

      added = true
      refreshCart(result)
    }

    if (added && config.redirectToCart) window.location.href = config.cartUrl
  }

  // Classic themes refresh the mini cart from added_to_cart. The Mini-Cart
  // block listens to it too when jQuery is on the page, and to its own event
  // otherwise.
  function refreshCart(result) {
    if (window.jQuery) {
      window.jQuery(document.body).trigger('added_to_cart', [result.fragments, result.cart_hash])
    } else {
      document.body.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart'))
    }
  }
})()
