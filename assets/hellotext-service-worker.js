self.addEventListener('install', event => {
  event.waitUntil(self.skipWaiting())
})

self.addEventListener('push', event => {
  if (!event.data) return

  let payload

  try {
    payload = event.data.json()
  } catch (error) {
    return
  }

  if (
    !payload ||
    payload.source !== 'hellotext' ||
    typeof payload.title !== 'string' ||
    !payload.title
  ) {
    return
  }

  const options = payload.options || {}

  event.waitUntil(
    self.registration.showNotification(payload.title, {
      ...options,
      data: { ...options.data, source: 'hellotext' },
    }),
  )
})

self.addEventListener('notificationclick', event => {
  const { data } = event.notification

  if (!data || data.source !== 'hellotext') return

  event.notification.close()

  let url

  try {
    url = new URL(data.actions?.[event.action] || data.url || '/', self.location.origin)
  } catch (error) {
    return
  }

  if (url.protocol !== 'https:' && url.protocol !== 'http:') return

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
      const client = clients.find(windowClient => windowClient.url === url.href)

      return client ? client.focus() : self.clients.openWindow(url.href)
    }),
  )
})
