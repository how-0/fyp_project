export {}

declare global {
  interface Window {
    google: {
      maps: {
        Map: new (el: HTMLElement, opts: Record<string, unknown>) => unknown
        Marker: new (opts: Record<string, unknown>) => {
          setMap: (map: unknown) => void
          addListener: (event: string, fn: () => void) => void
        }
        LatLngBounds: new () => { extend: (pos: unknown) => void }
        SymbolPath: { CIRCLE: unknown }
        places: {
          Autocomplete: new (input: HTMLInputElement, opts: Record<string, unknown>) => {
            addListener: (event: string, fn: () => void) => void
            getPlace: () => { formatted_address?: string; name?: string }
          }
        }
      }
    }
  }
}
