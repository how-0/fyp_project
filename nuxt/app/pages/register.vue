<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow p-8">
      <h1 class="text-2xl font-bold text-center mb-6">Create Account</h1>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block mb-1">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded-lg p-3"
            required
          />
        </div>

        <div>
          <label class="block mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded-lg p-3"
            required
          />
        </div>

        <div>
          <label class="block mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full border rounded-lg p-3"
            required
          />
        </div>

        <div>
          <label class="block mb-1">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="w-full border rounded-lg p-3"
            required
          />
        </div>

        <button
          class="w-full bg-blue-600 text-white rounded-lg p-3 hover:bg-blue-700 disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? "Creating account..." : "Register" }}
        </button>

        <p v-if="error" class="text-red-500 text-sm">
          {{ error }}
        </p>
      </form>

      <div class="text-center mt-6">
        Already have an account?
        <NuxtLink to="/login" class="text-blue-600"> Login </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
const loading = ref(false);
const error = ref("");

const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const config = useRuntimeConfig();
const router = useRouter();

const register = async () => {
  error.value = "";

  if (form.password !== form.password_confirmation) {
    error.value = "Passwords do not match.";
    return;
  }

  loading.value = true;

  try {
    await $fetch(`${config.public.apiBase}/register`, {
      method: "POST",
      body: form,
    });

    router.push("/login");
  } catch (err) {
    error.value = err?.data?.message ?? "Registration failed.";
  }

  loading.value = false;
};
</script>
