@extends('pdf.layouts.document')

@section('title', $receipt->number)

@section('document_title', 'Bon de réception')

@section('document_meta')
    <strong>N° :</strong> {{ $receipt->number }}<br>
    <strong>Date de réception :</strong> {{ $receipt->received_at?->format('d/m/Y') ?? '—' }}<br>
    @if ($receipt->purchaseOrder)
        <strong>BC d'origine :</strong> {{ $receipt->purchaseOrder->number }}<br>
    @endif
    <strong>Facture fournisseur :</strong> {{ $receipt->invoice_number ?? '—' }}
@endsection

@section('content')
    {{-- Fournisseur / Lieu de réception --}}
    <table class="address-table">
        <tr>
            <td class="address-box">
                <div class="label">Fournisseur</div>
                <div class="name">{{ $receipt->supplier?->name }}</div>
                <div class="detail">
                    Code : {{ $receipt->supplier?->code ?? '—' }}<br>
                    @if ($receipt->supplier?->contact_name)
                        Contact : {{ $receipt->supplier->contact_name }}<br>
                    @endif
                    @if ($receipt->supplier?->phone)
                        Tél : {{ $receipt->supplier->phone }}
                    @endif
                </div>
            </td>
            <td class="address-box">
                <div class="label">Reçu à</div>
                <div class="name">{{ $receipt->warehouse?->code }} — {{ $receipt->warehouse?->name }}</div>
                <div class="detail">
                    @if ($receipt->warehouse?->address)
                        {{ $receipt->warehouse->address }}<br>
                    @endif
                    @if ($receipt->warehouse?->city)
                        {{ $receipt->warehouse->city }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes valorisées --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 14%;">Réf</th>
                <th>Désignation</th>
                <th class="num" style="width: 11%;">Commandé</th>
                <th class="num" style="width: 9%;">Reçu</th>
                <th class="num" style="width: 13%;">PU (DH)</th>
                <th class="num" style="width: 15%;">Total ligne (DH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>
                        {{ $line->product?->name ?? '—' }}
                        @if ($line->over_receipt_reason)
                            <br><span class="muted" style="font-size: 7.5pt;">Sur-réception : {{ $line->over_receipt_reason }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $line->purchaseOrderLine?->quantity ?? '—' }}</td>
                    <td class="num">{{ $line->quantity }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line->lineTotal(), 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;">Total HT</td>
                <td class="num">{{ number_format($totalAmount, 2, ',', ' ') }} DH</td>
            </tr>
        </tfoot>
    </table>

    {{-- Notes --}}
    <div class="notes-box">
        <div class="label">Notes</div>
        <div>{{ $receipt->notes ?? '' }}</div>
    </div>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="label">Réceptionné par</div>
            </td>
            <td class="signature-box">
                <div class="label">Contrôlé par</div>
            </td>
        </tr>
    </table>
@endsection
