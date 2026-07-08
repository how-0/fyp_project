<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center py-12">
    <div class="w-10/12 max-w-md">
      <div class="text-center mb-8">
        <NuxtLink to="/" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition-colors">
          MY Trip Planner
        </NuxtLink>
        <p class="text-gray-500 mt-2 text-sm">Plan your perfect Malaysia trip with AI</p>
      </div>
      <slot />
    </div>
  </div>
</template>
