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
    optimizeDeps: {
      include: [
        'dayjs',
        'dayjs/locale/zh-cn',
        'dayjs/plugin/advancedFormat',
        'dayjs/plugin/customParseFormat',
        'dayjs/plugin/isBetween',
        'dayjs/plugin/quarterOfYear',
        'dayjs/plugin/weekOfYear',
        'dayjs/plugin/weekYear',
      ],
    },
    server: {
      proxy: {
        '/api': {
          target: 'http://localhost:8000',
          changeOrigin: true,
        },
        '/sanctum': {
          target: 'http://localhost:8000',
          changeOrigin: true,
        },
      },
    },
  },
  modules: ['nuxt-auth-sanctum', 'arco-design-nuxt-module'],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || '/api',
      googleMapsKey: process.env.NUXT_PUBLIC_GOOGLE_MAPS_KEY || '',
    },
  },
  sanctum: {
    baseUrl: process.env.NUXT_PUBLIC_SANCTUM_BASE_URL || 'http://localhost:3000',
    endpoints: {
      login: '/api/login',
      logout: '/api/logout',
      user: '/api/user',
      csrf: '/sanctum/csrf-cookie',
    },
    redirect: {
      keepRequestedRoute: true,
      onLogin: '/itineraries',
      onLogout: '/login',
    },
  },
})
