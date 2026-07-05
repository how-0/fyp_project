<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <NuxtLink to="/itineraries" class="text-xl font-bold text-blue-600">
          MY Trip Planner
        </NuxtLink>

        <nav v-if="isAuthenticated" class="flex items-center gap-4">
          <NuxtLink to="/itineraries" class="text-gray-600 hover:text-blue-600">My Trips</NuxtLink>
          <NuxtLink to="/itineraries/create" class="text-gray-600 hover:text-blue-600">Plan New</NuxtLink>
          <NuxtLink to="/itineraries/compare" class="text-gray-600 hover:text-blue-600">Compare</NuxtLink>
          <span class="text-sm text-gray-500">{{ user?.name }}</span>
          <a-button size="small" @click="handleLogout">Logout</a-button>
        </nav>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
const { isAuthenticated, user, logout } = useSanctumAuth()

const handleLogout = async () => {
  await logout()
  await navigateTo('/login')
}
</script>
