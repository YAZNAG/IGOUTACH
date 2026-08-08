import { ImagePlus, X } from 'lucide-react'
import { useRef } from 'react'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'

/** Saisie commune à tout chèque, quelle que soit sa provenance. */
export interface ChequeDraft {
  number: string
  cheque_date: string
  bank: string
  /** Nom porté sur le chèque. Vide = celui du client / le nôtre. */
  drawer_name: string
  image: File | null
}

export function chequeDraftVide(): ChequeDraft {
  return { number: '', cheque_date: '', bank: '', drawer_name: '', image: null }
}

/** Un chèque n'est exploitable que si l'on peut l'identifier et le présenter. */
export function chequeDraftComplet(draft: ChequeDraft): boolean {
  return draft.number.trim() !== '' && draft.cheque_date !== ''
}

interface ChequeDraftFieldsProps {
  value: ChequeDraft
  onChange: (draft: ChequeDraft) => void
  /** Masque le champ nom quand le parent le pilote lui-même. */
  hideDrawerName?: boolean
  drawerNameLabel?: string
}

export function ChequeDraftFields({
  value,
  onChange,
  hideDrawerName = false,
  drawerNameLabel = 'Nom porté sur le chèque',
}: ChequeDraftFieldsProps) {
  const inputImage = useRef<HTMLInputElement>(null)

  function set<K extends keyof ChequeDraft>(cle: K, v: ChequeDraft[K]) {
    onChange({ ...value, [cle]: v })
  }

  function choisirImage(files: FileList | null) {
    const file = files?.[0] ?? null

    if (file && file.size > 4 * 1024 * 1024) {
      window.alert('L’image dépasse 4 Mo.')
      return
    }

    set('image', file)
    if (inputImage.current) inputImage.current.value = ''
  }

  return (
    <div className="space-y-3">
      <div className="grid gap-3 sm:grid-cols-3">
        <Field label="N° / série du chèque" htmlFor="cheque-number">
          <Input
            id="cheque-number"
            value={value.number}
            onChange={(e) => set('number', e.target.value)}
            placeholder="1234567"
          />
        </Field>
        <Field label="Date du chèque" htmlFor="cheque-date">
          <Input
            id="cheque-date"
            type="date"
            value={value.cheque_date}
            onChange={(e) => set('cheque_date', e.target.value)}
          />
        </Field>
        <Field label="Banque" htmlFor="cheque-bank">
          <Input
            id="cheque-bank"
            value={value.bank}
            onChange={(e) => set('bank', e.target.value)}
            placeholder="Optionnel"
          />
        </Field>
      </div>

      {!hideDrawerName ? (
        <Field label={drawerNameLabel} htmlFor="cheque-drawer">
          <Input
            id="cheque-drawer"
            value={value.drawer_name}
            onChange={(e) => set('drawer_name', e.target.value)}
          />
        </Field>
      ) : null}

      <div className="flex items-center gap-3">
        <input
          ref={inputImage}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          className="hidden"
          onChange={(e) => choisirImage(e.target.files)}
        />
        <button
          type="button"
          onClick={() => inputImage.current?.click()}
          className="flex items-center gap-2 rounded-lg border border-line px-3 py-1.5 text-sm text-muted transition-colors hover:bg-bg hover:text-ink"
        >
          <ImagePlus className="h-4 w-4" />
          {value.image ? 'Remplacer la photo' : 'Photo du chèque'}
        </button>

        {value.image ? (
          <span className="flex items-center gap-2">
            {/* Aperçu local : le fichier ne part qu'à l'enregistrement. */}
            <img
              src={URL.createObjectURL(value.image)}
              alt=""
              className="h-10 w-16 rounded border border-line bg-bg object-contain"
            />
            <button
              type="button"
              onClick={() => set('image', null)}
              aria-label="Retirer la photo"
              className="text-muted transition-colors hover:text-bad"
            >
              <X className="h-4 w-4" />
            </button>
          </span>
        ) : (
          <span className="text-xs text-faint">Facultatif — JPG, PNG ou WebP, 4 Mo max.</span>
        )}
      </div>
    </div>
  )
}
