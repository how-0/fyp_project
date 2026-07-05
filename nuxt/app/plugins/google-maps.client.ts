export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const key = config.public.googleMapsKey

  if (!key || import.meta.server) return

  const scriptId = 'google-maps-script'
  if (document.getElementById(scriptId)) return

  const script = document.createElement('script')
  script.id = scriptId
  script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places&loading=async`
  script.async = true
  script.defer = true
  document.head.appendChild(script)
})
