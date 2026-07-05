<script setup>
const { isAuthenticated } = useSanctumAuth()

if (isAuthenticated.value) {
  await navigateTo('/itineraries')
} else {
  await navigateTo('/login')
}
</script>
