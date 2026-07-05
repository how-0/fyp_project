<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <a-card class="w-full max-w-md shadow-lg">
      <h1 class="text-2xl font-bold text-center mb-6">
        Login
      </h1>

      <a-form @submit="handleLogin" layout="vertical">

        <!-- Email -->
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

        <!-- Password -->
        <a-form-item label="Password">
          <a-input-password
            v-model="form.password"
            placeholder="Enter your password"
          />
        </a-form-item>

        <!-- Error -->
        <a-alert
          v-if="error"
          type="error"
          :content="error"
          class="mb-4"
        />

        <!-- Button -->
        <a-button
          html-type="submit"
          type="primary"
          long
          :loading="loading"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </a-button>

      </a-form>
    </a-card>
  </div>
</template>

<script setup>
import { IconUser } from '@arco-design/web-vue/es/icon'

const { login } = useSanctumAuth()

const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    await login(form)
    await navigateTo('/itineraries')
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      'Invalid email or password'
  } finally {
    loading.value = false
  }
}
</script>