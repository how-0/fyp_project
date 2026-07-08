import type { ActivitySuggestion, GenerateParams, Itinerary } from '~/types/itinerary'

export const useItinerary = () => {
  const client = useSanctumClient()

  const list = () => client<Itinerary[]>('/api/itineraries')

  const get = (id: number | string) => client<Itinerary>(`/api/itineraries/${id}`)

  const generate = (params: GenerateParams) =>
    client<Itinerary>('/api/itineraries/generate', { method: 'POST', body: params })

  const generateOutline = (params: GenerateParams) =>
    client<Itinerary>('/api/itineraries/generate-outline', { method: 'POST', body: params })

  const generateDay = (id: number | string, dayNumber: number) =>
    client<Itinerary>(`/api/itineraries/${id}/generate-day`, {
      method: 'POST',
      body: { day_number: dayNumber },
    })

  const update = (id: number | string, data: Partial<Itinerary>) =>
    client<Itinerary>(`/api/itineraries/${id}`, { method: 'PATCH', body: data })

  const remove = (id: number | string) =>
    client(`/api/itineraries/${id}`, { method: 'DELETE' })

  const duplicate = (id: number | string) =>
    client<Itinerary>(`/api/itineraries/${id}/duplicate`, { method: 'POST' })

  const compare = (ids: number[]) =>
    client('/api/itineraries/compare', { method: 'POST', body: { ids } })

  const reorder = (id: number | string, activities: { id: number; day_id: number; sort_order: number }[]) =>
    client<Itinerary>(`/api/itineraries/${id}/activities/reorder`, {
      method: 'PATCH',
      body: { activities },
    })

  const updateActivity = (
    itineraryId: number | string,
    activityId: number,
    data: Record<string, unknown>,
  ) =>
    client<Itinerary>(`/api/itineraries/${itineraryId}/activities/${activityId}`, {
      method: 'PATCH',
      body: data,
    })

  const deleteActivity = (itineraryId: number | string, activityId: number) =>
    client<Itinerary>(`/api/itineraries/${itineraryId}/activities/${activityId}`, {
      method: 'DELETE',
    })

  const suggestAlternative = (
    itineraryId: number | string,
    activityId: number,
    context?: string,
  ) =>
    client<{ suggestions: ActivitySuggestion[] }>(
      `/api/itineraries/${itineraryId}/activities/${activityId}/suggest`,
      { method: 'POST', body: { context } },
    )

  const regenerateDay = (id: number | string, dayNumber: number, notes?: string) =>
    client<Itinerary>(`/api/itineraries/${id}/regenerate`, {
      method: 'POST',
      body: { day_number: dayNumber, notes },
    })

  return {
    list,
    get,
    generate,
    generateOutline,
    generateDay,
    update,
    remove,
    duplicate,
    compare,
    reorder,
    updateActivity,
    deleteActivity,
    suggestAlternative,
    regenerateDay,
  }
}
