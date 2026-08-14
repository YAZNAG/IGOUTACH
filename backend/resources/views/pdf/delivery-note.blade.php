@extends('pdf.layouts.document')

@section('title', 'BL-'.$sale->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">LIVRAISON</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">BL-{{ $sale->reference }}</td></tr>
        <tr><td class="k">Vente</td><td class="v">{{ $sale->reference }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y') ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Client --}}
    <table class="address-table address-solo">
        <tr>
            <td class="address-box">
                <div class="label">Destinataire</div>
                <div class="name">{{ $sale->customer?->name ?? 'Client de passage' }}</div>
                <div class="detail">
                    @if ($sale->customer?->code)
                        Code : {{ $sale->customer->code }}<br>
                    @endif
                    @if ($sale->customer?->phone)
                        Tél : {{ $sale->customer->phone }}<br>
                    @endif
                    @if ($sale->customer?->address)
                        {{ $sale->customer->address }}@if ($sale->customer?->city), {{ $sale->customer->city }}@endif
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes avec les prix pratiqués sur cette vente : ce sont ceux-là qui
         font foi, y compris quand le vendeur a négocié un prix pour cette
         vente-là — pas le tarif du catalogue. --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 16%;">Référence</th>
                <th>Désignation</th>
                <th class="num" style="width: 9%;">Qté</th>
                <th class="num" style="width: 14%;">PU (DH)</th>
                <th class="num" style="width: 16%;">Total (DH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num">{{ $line->quantity }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format((float) $line->line_total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr>
                        <td class="k">Références</td>
                        <td class="v">{{ $lines->count() }}</td>
                    </tr>
                    <tr>
                        <td class="k">Quantité livrée</td>
                        <td class="v">{{ $lines->sum('quantity') }} unité{{ $lines->sum('quantity') > 1 ? 's' : '' }}</td>
                    </tr>
                    @if ((float) $sale->discount_percent > 0)
                        <tr>
                            <td class="k">Sous-total</td>
                            <td class="v">{{ number_format((float) $sale->subtotal, 2, ',', ' ') }} DH</td>
                        </tr>
                        <tr>
                            <td class="k">Remise ({{ number_format((float) $sale->discount_percent, 1, ',', ' ') }} %)</td>
                            <td class="v">-{{ number_format((float) $sale->subtotal - (float) $sale->total, 2, ',', ' ') }} DH</td>
                        </tr>
                    @endif
                    {{-- Le total reprend celui de la facture : deux documents
                         d'une même vente ne doivent pas annoncer deux montants. --}}
                    <tr class="grand">
                        <td class="k">TOTAL</td>
                        <td class="v">{{ number_format((float) $sale->total, 2, ',', ' ') }} DH</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes --}}
    <div class="notes-box">
        <div class="label">Notes</div>
        <div>{{ $sale->note ?? '' }}</div>
    </div>
@endsection
