import { ImagePlus, Star, Trash2 } from 'lucide-react'
import { useRef, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import {
  useDeleteProductImage,
  useProductImages,
  useSetMainProductImage,
  useUploadProductImage,
} from '../hooks'

interface MediaTabProps {
  productId: number
}

const FORMATS = 'image/jpeg,image/png,image/webp'
const TAILLE_MAX_MO = 4

export function MediaTab({ productId }: MediaTabProps) {
  const can = usePermission()
  const canManage = can('product.media_manage')

  const { data: images, isLoading } = useProductImages(productId)
  const upload = useUploadProductImage(productId)
  const setMain = useSetMainProductImage(productId)
  const remove = useDeleteProductImage(productId)

  const inputRef = useRef<HTMLInputElement>(null)
  const [erreur, setErreur] = useState<string | null>(null)

  async function handleFiles(files: FileList | null) {
    if (!files || files.length === 0) return
    setErreur(null)

    // Envoi séquentiel : le serveur renvoie la galerie complète à chaque
    // image, deux envois simultanés s'écraseraient mutuellement dans le cache.
    for (const file of Array.from(files)) {
      if (file.size > TAILLE_MAX_MO * 1024 * 1024) {
        setErreur(`« ${file.name} » dépasse ${TAILLE_MAX_MO} Mo.`)
        continue
      }

      try {
        await upload.mutateAsync(file)
      } catch {
        setErreur(`Échec du téléversement de « ${file.name} ».`)
      }
    }

    if (inputRef.current) inputRef.current.value = ''
  }

  return (
    <Card>
      <CardHeader
        title="Images de l'article"
        hint="Une image principale et autant de secondaires que nécessaire. Les images sont facultatives."
        action={
          canManage ? (
            <Button size="sm" onClick={() => inputRef.current?.click()} disabled={upload.isPending}>
              <ImagePlus className="h-4 w-4" />
              {upload.isPending ? 'Envoi…' : 'Ajouter une image'}
            </Button>
          ) : null
        }
      />
      <CardBody className="space-y-4">
        <input
          ref={inputRef}
          type="file"
          accept={FORMATS}
          multiple
          className="hidden"
          onChange={(e) => handleFiles(e.target.files)}
        />

        {erreur ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
        ) : null}

        {isLoading ? (
          <p className="py-8 text-center text-sm text-muted">Chargement…</p>
        ) : !images || images.length === 0 ? (
          <div className="rounded-lg border border-dashed border-line py-12 text-center">
            <p className="text-sm text-muted">Aucune image pour cet article.</p>
            {canManage ? (
              <p className="mt-1 text-xs text-faint">
                JPG, PNG ou WebP — {TAILLE_MAX_MO} Mo maximum par image.
              </p>
            ) : null}
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {images.map((image) => (
              <figure
                key={image.id}
                className="group relative overflow-hidden rounded-lg border border-line bg-bg"
              >
                <img
                  src={image.url}
                  alt=""
                  loading="lazy"
                  className="aspect-square w-full object-contain"
                />

                {image.is_main ? (
                  <figcaption className="absolute left-2 top-2">
                    <Badge tone="ok">Principale</Badge>
                  </figcaption>
                ) : null}

                {canManage ? (
                  <div className="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-[rgba(15,27,45,0.72)] p-2 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                    {!image.is_main ? (
                      <button
                        type="button"
                        onClick={() => setMain.mutate(image.id)}
                        disabled={setMain.isPending}
                        title="Définir comme image principale"
                        aria-label="Définir comme image principale"
                        className="flex h-8 w-8 items-center justify-center rounded-lg bg-card text-muted transition-colors hover:text-warn"
                      >
                        <Star className="h-4 w-4" />
                      </button>
                    ) : null}
                    <button
                      type="button"
                      onClick={() => remove.mutate(image.id)}
                      disabled={remove.isPending}
                      title="Supprimer l'image"
                      aria-label="Supprimer l'image"
                      className="flex h-8 w-8 items-center justify-center rounded-lg bg-card text-muted transition-colors hover:text-bad"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                ) : null}
              </figure>
            ))}
          </div>
        )}
      </CardBody>
    </Card>
  )
}
