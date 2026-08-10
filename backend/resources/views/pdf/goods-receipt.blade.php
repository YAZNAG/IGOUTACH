@extends('pdf.layouts.document')

@section('title', $receipt->number)

@section('document_title')<span class="a">BON DE </span><span class="b">RÉCEPTION</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $receipt->number }}</td></tr>
        <tr><td class="k">Réception</td><td class="v">{{ $receipt->received_at?->format('d/m/Y') ?? '—' }}</td></tr>
        @if ($receipt->purchaseOrder)
            <tr><td class="k">BC d'origine</td><td class="v">{{ $receipt->purchaseOrder->number }}</td></tr>
        @endif
        <tr><td class="k">Facture fourn.</td><td class="v">{{ $receipt->invoice_number ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Fournisseur --}}
    <table class="address-table address-solo">
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
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr class="grand">
                        <td class="k">TOTAL</td>
                        <td class="v">{{ number_format($totalAmount, 2, ',', ' ') }} DH</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes --}}
    <div class="notes-box">
        <div class="label">Notes</div>
        <div>{{ $receipt->notes ?? '' }}</div>
    </div>
@endsection
