export function useAuthRedirect() {
  const { isAuthenticated } = useSanctumAuth()

  function isLoggedIn(): boolean {
    return isAuthenticated.value
  }

  async function redirectToItineraryIfLoggedIn(): Promise<boolean> {
    if (isLoggedIn()) {
      await navigateTo('/itineraries')
      return true
    }
    return false
  }

  return {
    isLoggedIn,
    redirectToItineraryIfLoggedIn,
  }
}
