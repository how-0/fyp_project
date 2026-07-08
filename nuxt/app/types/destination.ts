export interface Destination {
  id: number
  name: string
  state: string
  category: string | null
  description: string | null
  image_url: string | null
}

export function formatDestinationLocation(destination: Pick<Destination, 'name' | 'state'>): string {
  return `${destination.name}, ${destination.state}, Malaysia`
}
