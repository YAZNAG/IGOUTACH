export interface WarehouseType {
  id: number
  code: string
  name: string
  allows_sales: boolean
  allows_purchase_receipt: boolean
  requires_transfer_approval: boolean
}

export interface Warehouse {
  id: number
  code: string
  name: string
  warehouse_type_id: number
  type?: WarehouseType | null
  manager_id: number | null
  parent_id: number | null
  address: string | null
  city: string | null
  phone: string | null
  is_active: boolean
  /** TVA facturée par ce lieu. 0 = hors taxe, comme avant. */
  vat_rate: number
  /** Prix saisis TVA comprise : la taxe s'extrait au lieu de s'ajouter. */
  prices_include_vat: boolean
}

export interface WarehouseInput {
  code: string
  name: string
  warehouse_type_id: number
  parent_id: number | null
  address: string | null
  city: string | null
  phone: string | null
  is_active: boolean
  vat_rate: number
  prices_include_vat: boolean
}
