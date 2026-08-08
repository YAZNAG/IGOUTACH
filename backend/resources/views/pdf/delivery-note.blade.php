@extends('pdf.layouts.document')

@section('title', 'BS-'.$sale->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">SORTIE</span>@endsection

@section('document_meta')
    <strong>N° :</strong> BS-{{ $sale->reference }}<br>
    <strong>Vente :</strong> {{ $sale->reference }}<br>
    <strong>Date :</strong> {{ ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y') ?? '—' }}
@endsection

@section('content')
    {{-- Client / Lieu de sortie --}}
    <table class="address-table">
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
            <td class="address-box">
                <div class="label">Sortie depuis</div>
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

    {{-- Lignes : quantités seules, aucun montant (document de livraison) --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 18%;">Référence</th>
                <th>Désignation</th>
                <th class="num" style="width: 10%;">Qté</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num">{{ $line->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right;">Total</td>
                <td class="num">{{ $lines->count() }} réf · {{ $lines->sum('quantity') }} unités</td>
            </tr>
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
                <div class="label">Reçu par (client)</div>
            </td>
            <td class="signature-box">
                <div class="label">Préparé par</div>
            </td>
        </tr>
    </table>
@endsection
