<template>
  <a-card class="shadow-lg border-0">
    <h1 class="text-2xl font-bold text-center mb-2">Create Account</h1>
    <p class="text-gray-500 text-center mb-6 text-sm">
      Start planning your Malaysia adventures
    </p>

    <a-form @submit="register" layout="vertical">
      <a-form-item label="Name">
        <a-input v-model="form.name" placeholder="Your name" />
      </a-form-item>

      <a-form-item label="Email">
        <a-input v-model="form.email" placeholder="your@email.com" />
      </a-form-item>

      <a-form-item label="Password">
        <a-input-password v-model="form.password" placeholder="Create a password" />
      </a-form-item>

      <a-form-item label="Confirm Password">
        <a-input-password v-model="form.password_confirmation" placeholder="Confirm your password" />
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
        {{ loading ? 'Creating account...' : 'Register' }}
      </a-button>
    </a-form>

    <div class="text-center mt-6 text-sm text-gray-500">
      Already have an account?
      <NuxtLink to="/login" class="text-blue-600 hover:text-blue-700 font-medium">
        Login
      </NuxtLink>
    </div>
  </a-card>
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:guest',
  layout: 'auth',
})

const client = useSanctumClient()
const { refreshIdentity } = useSanctumAuth()
const { redirectToItineraryIfLoggedIn } = useAuthRedirect()

const loading = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  await redirectToItineraryIfLoggedIn()
})

const register = async () => {
  error.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match.'
    return
  }

  loading.value = true

  try {
    await client('/api/register', {
      method: 'POST',
      body: form,
    })

    await refreshIdentity()
    await navigateTo('/itineraries')
  } catch (err) {
    error.value = err?.data?.message ?? 'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>
