import { Save } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import { useSettings, useUpdateSettings } from '../hooks'
import type { SettingValue } from '../api/settingsApi'

type FieldType = 'text' | 'bool' | 'int' | 'locale' | 'valuation'

interface FieldMeta {
  key: string
  label: string
  type: FieldType
}

const GROUP_TITLES: Record<string, string> = {
  company: 'Société',
  rules: 'Règles de gestion',
  print: "Modèles d'impression",
  general: 'Général',
}

const FIELD_META: Record<string, FieldMeta> = {
  company_name: { key: 'company_name', label: 'Raison sociale', type: 'text' },
  company_ice: { key: 'company_ice', label: 'ICE', type: 'text' },
  company_rc: { key: 'company_rc', label: 'RC', type: 'text' },
  company_if: { key: 'company_if', label: 'IF', type: 'text' },
  company_patente: { key: 'company_patente', label: 'Patente', type: 'text' },
  company_address: { key: 'company_address', label: 'Adresse', type: 'text' },
  company_city: { key: 'company_city', label: 'Ville', type: 'text' },
  company_phone: { key: 'company_phone', label: 'Téléphone', type: 'text' },
  company_email: { key: 'company_email', label: 'E-mail', type: 'text' },
  stock_valuation_method: { key: 'stock_valuation_method', label: 'Méthode de valorisation', type: 'valuation' },
  allow_negative_stock: { key: 'allow_negative_stock', label: 'Autoriser le stock négatif', type: 'bool' },
  max_discount_percent: { key: 'max_discount_percent', label: 'Remise maximale (%)', type: 'int' },
  print_header: { key: 'print_header', label: "En-tête d'impression", type: 'text' },
  print_footer: { key: 'print_footer', label: "Pied de page d'impression", type: 'text' },
  print_show_logo: { key: 'print_show_logo', label: 'Afficher le logo', type: 'bool' },
  locale: { key: 'locale', label: 'Langue', type: 'locale' },
  currency: { key: 'currency', label: 'Devise', type: 'text' },
}

export function GeneralSettingsPage() {
  const can = usePermission()
  const canManage = can('settings.manage')
  const { data, isLoading } = useSettings()
  const updateMutation = useUpdateSettings()

  const [values, setValues] = useState<Record<string, SettingValue>>({})

  useEffect(() => {
    if (data) {
      const flat: Record<string, SettingValue> = {}
      for (const group of Object.values(data.data)) {
        for (const [key, value] of Object.entries(group)) flat[key] = value
      }
      setValues(flat)
    }
  }, [data])

  if (isLoading || !data) {
    return <p className="text-sm text-muted">Chargement…</p>
  }

  function setValue(key: string, value: SettingValue) {
    setValues((prev) => ({ ...prev, [key]: value }))
  }

  function save() {
    updateMutation.mutate(values)
  }

  function renderField(key: string) {
    const meta = FIELD_META[key]
    if (!meta) return null
    const value = values[key]

    if (meta.type === 'bool') {
      return (
        <Field key={key} label={meta.label} htmlFor={`s-${key}`}>
          <label className="inline-flex items-center gap-2 text-sm text-ink">
            <input
              id={`s-${key}`}
              type="checkbox"
              checked={Boolean(value)}
              disabled={!canManage}
              onChange={(e) => setValue(key, e.target.checked)}
            />
            {meta.label}
          </label>
        </Field>
      )
    }

    if (meta.type === 'locale') {
      return (
        <Field key={key} label={meta.label} htmlFor={`s-${key}`}>
          <Select id={`s-${key}`} value={String(value)} disabled={!canManage} onChange={(e) => setValue(key, e.target.value)}>
            <option value="fr">Français</option>
            <option value="ar">العربية</option>
          </Select>
        </Field>
      )
    }

    if (meta.type === 'valuation') {
      return (
        <Field key={key} label={meta.label} htmlFor={`s-${key}`}>
          <Select id={`s-${key}`} value={String(value)} disabled={!canManage} onChange={(e) => setValue(key, e.target.value)}>
            <option value="cmup">CMUP (coût moyen pondéré)</option>
          </Select>
        </Field>
      )
    }

    return (
      <Field key={key} label={meta.label} htmlFor={`s-${key}`}>
        <Input
          id={`s-${key}`}
          type={meta.type === 'int' ? 'number' : 'text'}
          min={meta.type === 'int' ? 0 : undefined}
          value={value === undefined || value === null ? '' : String(value)}
          disabled={!canManage}
          onChange={(e) => setValue(key, meta.type === 'int' ? Number(e.target.value) : e.target.value)}
        />
      </Field>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-ink">Paramètres généraux</h2>
          <p className="text-sm text-muted">Société, règles de gestion, impression et langue.</p>
        </div>
        {canManage ? (
          <Button onClick={save} disabled={updateMutation.isPending}>
            <Save className="h-4 w-4" />
            {updateMutation.isPending ? 'Enregistrement…' : 'Enregistrer'}
          </Button>
        ) : null}
      </div>

      {updateMutation.isSuccess && !updateMutation.isPending ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">Paramètres enregistrés.</p>
      ) : null}

      {data.groups.map((group) => (
        <Card key={group}>
          <CardHeader title={GROUP_TITLES[group] ?? group} />
          <CardBody>
            <div className="grid gap-4 sm:grid-cols-2">
              {Object.keys(data.data[group] ?? {}).map((key) => renderField(key))}
            </div>
          </CardBody>
        </Card>
      ))}
    </div>
  )
}
