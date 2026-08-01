import { api } from './api'

/**
 * Télécharge un fichier depuis l'API (authentifié par cookie) et déclenche
 * l'enregistrement côté navigateur.
 */
export async function downloadFile(
  url: string,
  filename: string,
  params?: Record<string, string | number | undefined>,
): Promise<void> {
  const response = await api.get<Blob>(url, { params, responseType: 'blob' })
  const objectUrl = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = objectUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(objectUrl)
}
