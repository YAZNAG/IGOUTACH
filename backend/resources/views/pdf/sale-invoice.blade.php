@extends('pdf.layouts.document')

@section('title', $sale->reference)

@section('document_title')
    @if ($sale->type === 'quote')
        <span class="a">DE</span><span class="b">VIS</span>
    @else
        <span class="a">FAC</span><span class="b">TURE</span>
    @endif
@endsection

@section('document_meta')
    <strong>N° :</strong> {{ $sale->reference }}<br>
    <strong>Date :</strong> {{ ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y') ?? '—' }}<br>
    <strong>Statut :</strong> {{ $sale->status === 'confirmed' ? 'Confirmée' : ($sale->status === 'cancelled' ? 'Annulée' : 'Brouillon') }}
@endsection

@section('content')
    {{-- Client / Lieu de vente --}}
    <table class="address-table">
        <tr>
            <td class="address-box">
                <div class="label">Client</div>
                <div class="name">{{ $sale->customer?->name ?? 'Client de passage' }}</div>
                <div class="detail">
                    @if ($sale->customer?->code)
                        Code : {{ $sale->customer->code }}<br>
                    @endif
                    @if ($sale->customer?->ice)
                        ICE : {{ $sale->customer->ice }}<br>
                    @endif
                    @if ($sale->customer?->phone)
                        Tél : {{ $sale->customer->phone }}<br>
                    @endif
                    @if ($sale->customer?->address)
                        {{ $sale->customer->address }}@if ($sale->customer?->city), {{ $sale->customer->city }}@endif
                    @endif
                </div>
            </td>
            <td class="address-box">
                <div class="label">Vendu depuis</div>
                <div class="name">{{ $sale->warehouse?->code }} — {{ $sale->warehouse?->name }}</div>
                <div class="detail">
                    @if ($sale->warehouse?->address)
                        {{ $sale->warehouse->address }}<br>
                    @endif
                    @if ($sale->warehouse?->city)
                        {{ $sale->warehouse->city }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes valorisées --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 15%;">Réf</th>
                <th>Désignation</th>
                <th class="num" style="width: 9%;">Qté</th>
                <th class="num" style="width: 14%;">PU (DH)</th>
                <th class="num" style="width: 16%;">Total ligne (DH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num">{{ $line->quantity }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format((float) $line->line_total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;">Sous-total</td>
                <td class="num">{{ number_format((float) $sale->subtotal, 2, ',', ' ') }} DH</td>
            </tr>
            @if ((float) $sale->discount_percent > 0)
                <tr>
                    <td colspan="4" style="text-align: right;">Remise ({{ number_format((float) $sale->discount_percent, 1, ',', ' ') }} %)</td>
                    <td class="num">-{{ number_format((float) $sale->subtotal - (float) $sale->total, 2, ',', ' ') }} DH</td>
                </tr>
            @endif
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                <td class="num"><strong>{{ number_format((float) $sale->total, 2, ',', ' ') }} DH</strong></td>
            </tr>
            @if ((float) $sale->paid_amount > 0)
                <tr>
                    <td colspan="4" style="text-align: right;">Payé</td>
                    <td class="num">{{ number_format((float) $sale->paid_amount, 2, ',', ' ') }} DH</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right;">Reste à payer</td>
                    <td class="num">{{ number_format(max(0, (float) $sale->total - (float) $sale->paid_amount), 2, ',', ' ') }} DH</td>
                </tr>
            @endif
        </tfoot>
    </table>

    {{-- Notes --}}
    <div class="notes-box">
        <div class="label">Notes</div>
        <div>{{ $sale->note ?? '' }}</div>
    </div>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="label">Le client</div>
            </td>
            <td class="signature-box">
                <div class="label">Le vendeur</div>
            </td>
        </tr>
    </table>
@endsection
