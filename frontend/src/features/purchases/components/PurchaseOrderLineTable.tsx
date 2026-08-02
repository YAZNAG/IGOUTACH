import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { PurchaseOrderLine } from '../api/purchaseOrdersApi'

interface PurchaseOrderLineTableProps {
  lines: PurchaseOrderLine[]
}

export function PurchaseOrderLineTable({ lines }: PurchaseOrderLineTableProps) {
  return (
    <Card>
      <CardHeader title="Lignes" />
      <CardBody className="p-0">
        {lines.length === 0 ? (
          <div className="p-5 text-center text-sm text-muted">Aucune ligne.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Article</th>
                  <th className="px-5 py-3 text-right font-medium">Commandé</th>
                  <th className="px-5 py-3 text-right font-medium">Déjà reçu</th>
                  <th className="px-5 py-3 text-right font-medium">Reliquat</th>
                </tr>
              </thead>
              <tbody>
                {lines.map((line) => (
                  <tr key={line.id} className="border-b border-line last:border-0">
                    <td className="mono px-5 py-3 text-muted">{line.product.sku ?? '—'}</td>
                    <td className="px-5 py-3 text-ink">{line.product.name ?? '—'}</td>
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">{line.quantity}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{line.received_quantity}</td>
                    <td
                      className={`tabular px-5 py-3 text-right font-medium ${
                        line.remaining > 0 ? 'text-warn' : 'text-ok'
                      }`}
                    >
                      {line.remaining}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardBody>
    </Card>
  )
}
