export function getApiErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  if (!error || typeof error !== 'object') {
    return fallback
  }

  const err = error as {
    data?: { message?: string; errors?: Record<string, string[]> }
    response?: { data?: { message?: string }; _data?: { message?: string } }
    message?: string
  }

  const body = err.data ?? err.response?._data ?? err.response?.data

  if (body?.message) {
    return body.message
  }

  const firstFieldError = body?.errors
    ? Object.values(body.errors).flat().find((msg) => typeof msg === 'string')
    : undefined

  if (firstFieldError) {
    return firstFieldError
  }

  if (err.message && !err.message.toLowerCase().includes('[post]') && !err.message.toLowerCase().includes('[get]')) {
    return err.message
  }

  return fallback
}
