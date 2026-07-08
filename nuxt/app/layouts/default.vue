<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
      <div class="w-10/12 max-w-screen-xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between">
        <NuxtLink to="/itineraries" class="text-xl font-bold text-blue-600 hover:text-blue-700 transition-colors">
          MY Trip Planner
        </NuxtLink>

        <nav v-if="isAuthenticated" class="flex items-center gap-4">
          <NuxtLink to="/itineraries" class="text-gray-600 hover:text-blue-600 transition-colors">My Trips</NuxtLink>
          <NuxtLink to="/itineraries/create" class="text-gray-600 hover:text-blue-600 transition-colors">Plan New</NuxtLink>
          <NuxtLink to="/itineraries/compare" class="text-gray-600 hover:text-blue-600 transition-colors">Compare</NuxtLink>
          <span class="text-sm text-gray-500 hidden sm:inline">{{ user?.name }}</span>
          <a-button size="small" status="danger" @click="handleLogout">
            Logout
          </a-button>
        </nav>
      </div>
    </header>

    <main class="w-10/12 max-w-screen-xl mx-auto px-6 lg:px-8 py-8">
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
