import { useMemo } from 'react'
import { Combobox } from '@/components/ui/Combobox'
import { useSupplierOptions } from '@/features/access/hooks'

interface SupplierComboboxProps {
  id?: string
  /** 0 : aucun fournisseur choisi. */
  value: number
  onChange: (id: number) => void
  placeholder?: string
  /** Faux sur un champ obligatoire : on ne peut pas revenir à « aucun ». */
  allowEmpty?: boolean
  disabled?: boolean
  className?: string
}

/**
 * Choix d'un fournisseur en tapant son nom ou son code.
 *
 * Partout où une liste déroulante proposait les fournisseurs, c'est ce champ
 * qui la remplace : bons de commande, réceptions, retours, historiques. La
 * liste des fournisseurs est celle que ces écrans chargeaient déjà.
 */
export function SupplierCombobox({
  id,
  value,
  onChange,
  placeholder = 'Nom ou code du fournisseur…',
  allowEmpty = true,
  disabled,
  className,
}: SupplierComboboxProps) {
  const { data: suppliers = [] } = useSupplierOptions()

  const options = useMemo(
    () => suppliers.map((s) => ({ id: s.id, label: s.name, hint: s.code })),
    [suppliers],
  )

  return (
    <Combobox
      id={id}
      value={value}
      onChange={onChange}
      options={options}
      placeholder={placeholder}
      allowEmpty={allowEmpty}
      disabled={disabled}
      className={className}
    />
  )
}
