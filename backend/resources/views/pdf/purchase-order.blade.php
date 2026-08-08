@extends('pdf.layouts.document')

@section('title', $order->number)

@section('document_title')<span class="a">BON DE </span><span class="b">COMMANDE</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $order->number }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ $order->ordered_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="k">Livraison prévue</td><td class="v">{{ $order->expected_at?->format('d/m/Y') ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Fournisseur / Livraison --}}
    <table class="address-table">
        <tr>
            <td class="address-box">
                <div class="label">Fournisseur</div>
                <div class="name">{{ $order->supplier?->name }}</div>
                <div class="detail">
                    Code : {{ $order->supplier?->code ?? '—' }}<br>
                    @if ($order->supplier?->contact_name)
                        Contact : {{ $order->supplier->contact_name }}<br>
                    @endif
                    @if ($order->supplier?->phone)
                        Tél : {{ $order->supplier->phone }}<br>
                    @endif
                    @if ($order->supplier?->email)
                        Email : {{ $order->supplier->email }}
                    @endif
                </div>
            </td>
            <td class="address-box">
                <div class="label">Livrer à</div>
                <div class="name">{{ $order->warehouse?->code }} — {{ $order->warehouse?->name }}</div>
                <div class="detail">
                    @if ($order->warehouse?->address)
                        {{ $order->warehouse->address }}<br>
                    @endif
                    @if ($order->warehouse?->city)
                        {{ $order->warehouse->city }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes (aucun montant, aucun prix : règle métier) --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 18%;">Référence</th>
                <th>Désignation</th>
                <th class="num" style="width: 10%;">Qté</th>
                <th class="center" style="width: 12%;">Unité</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num">{{ $line->quantity }}</td>
                    <td class="center">{{ $line->product?->unit?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr class="grand">
                        <td class="k">TOTAL COMMANDÉ</td>
                        <td class="v">{{ $lines->sum('quantity') }} unité{{ $lines->sum('quantity') > 1 ? 's' : '' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Références</td>
                        <td class="v">{{ $lines->count() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes --}}
    <div class="notes-box">
        <div class="label">Notes</div>
        <div>{{ $order->notes ?? '' }}</div>
    </div>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="label">Signature fournisseur</div>
            </td>
            <td class="signature-box">
                <div class="label">Signature responsable</div>
            </td>
        </tr>
    </table>
@endsection
