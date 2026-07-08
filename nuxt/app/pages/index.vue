<script setup>
const { isLoggedIn } = useAuthRedirect()

if (isLoggedIn()) {
  await navigateTo('/itineraries')
} else {
  await navigateTo('/login')
}
</script>
