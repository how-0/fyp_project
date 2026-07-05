// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  ssr: false,
  app: {
    baseURL: '/',
    buildAssetsDir: '/_nuxt/',
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'page', mode: 'out-in' },
  },
  experimental: {
    appManifest: false,
  },
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['./public/assets/css/main.css'],
  vite: {
    plugins: [
      tailwindcss(),
    ],
  },
  modules: ['nuxt-auth-sanctum', 'arco-design-nuxt-module'],
  sanctum: {
    baseUrl: 'http://localhost:8000', // Laravel API
  },
})