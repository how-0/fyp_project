<template>
  <a-card class="shadow-lg border-0">
    <h1 class="text-2xl font-bold text-center mb-2">
      Welcome back
    </h1>
    <p class="text-gray-500 text-center mb-6 text-sm">
      Sign in to manage your itineraries
    </p>

    <a-form @submit="handleLogin" layout="vertical">
      <a-form-item label="Email">
        <a-input
          v-model="form.email"
          placeholder="Enter your email"
        >
          <template #prefix>
            <icon-user />
          </template>
        </a-input>
      </a-form-item>

      <a-form-item label="Password">
        <a-input-password
          v-model="form.password"
          placeholder="Enter your password"
        />
      </a-form-item>

      <a-alert
        v-if="error"
        type="error"
        :content="error"
        class="mb-4"
      />

      <a-button
        html-type="submit"
        type="primary"
        long
        :loading="loading"
      >
        {{ loading ? 'Logging in...' : 'Login' }}
      </a-button>
    </a-form>

    <div class="text-center mt-6 text-sm text-gray-500">
      Don't have an account?
      <NuxtLink
        :to="{ path: '/register', query: route.query.redirect ? { redirect: route.query.redirect } : {} }"
        class="text-blue-600 hover:text-blue-700 font-medium"
      >
        Create one
      </NuxtLink>
    </div>
  </a-card>
</template>

<script setup>
import { IconUser } from '@arco-design/web-vue/es/icon'

definePageMeta({
  middleware: 'sanctum:guest',
  layout: 'auth',
})

const { login } = useSanctumAuth()
const { redirectToItineraryIfLoggedIn } = useAuthRedirect()
const route = useRoute()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

const redirectAfterLogin = async () => {
  const redirect = route.query.redirect
  if (typeof redirect === 'string' && redirect.startsWith('/')) {
    await navigateTo(redirect)
    return
  }
  await navigateTo('/itineraries')
}

onMounted(async () => {
  await redirectToItineraryIfLoggedIn()
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    await login(form)
    await redirectAfterLogin()
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      'Invalid email or password'
  } finally {
    loading.value = false
  }
}
</script>
