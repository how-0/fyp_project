import type { Destination } from '~/types/destination'

export const useDestination = () => {
  const featured = () => $fetch<Destination[]>('/api/destinations/featured')

  return { featured }
}
