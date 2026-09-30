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

/**
 * Ouvre un PDF de l'API dans un nouvel onglet, pret a imprimer.
 *
 * L'onglet est ouvert avant la requete : ouvert apres, il serait bloque par le
 * navigateur comme une fenetre surgissante. Si l'onglet est refuse malgre
 * tout, le fichier est telecharge a la place.
 */
export async function openPdf(url: string, filename: string): Promise<void> {
  const onglet = window.open('', '_blank')
  if (onglet) {
    onglet.document.title = filename
    onglet.document.body.style.fontFamily = 'sans-serif'
    onglet.document.body.textContent = 'Préparation du document…'
  }

  try {
    const response = await api.get<Blob>(url, { responseType: 'blob' })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const objectUrl = URL.createObjectURL(blob)

    if (onglet && !onglet.closed) {
      onglet.location.href = objectUrl
    } else {
      const link = document.createElement('a')
      link.href = objectUrl
      link.download = filename
      document.body.appendChild(link)
      link.click()
      link.remove()
    }
    // L'onglet a besoin de l'adresse le temps de charger le document.
    setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000)
  } catch (e) {
    onglet?.close()
    throw e
  }
}
