@extends('pdf.layouts.document')

@section('title', 'BS-'.$sale->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">SORTIE</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">BS-{{ $sale->reference }}</td></tr>
        <tr><td class="k">Vente</td><td class="v">{{ $sale->reference }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y à H:i') ?? '—' }}</td></tr>
        @php($etabli = $sale->created_at?->format('d/m/Y à H:i'))
        @php($affiche = ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y à H:i'))
        {{-- La plupart des ventes sont confirmees dans la minute : repeter le
             meme horodatage sur deux lignes n'apprend rien. On ne montre la
             date de saisie que lorsqu'elle differe de la date du document. --}}
        @if ($etabli !== null && $etabli !== $affiche)
        <tr><td class="k">Établi le</td><td class="v">{{ $etabli }}</td></tr>
        @endif
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
                        Tél : {{ $sale->customer->phone }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Quantités seules, aucun montant.

         C'est le document du magasinier : il sert à préparer et à contrôler ce
         qui quitte le dépôt. Y porter les prix les exposerait à toute la chaîne
         logistique, alors que le bon de livraison — remis au client — s'en
         charge déjà. --}}
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 20%;">Référence</th>
                <th>Désignation</th>
                <th class="num" style="width: 12%;">Qté</th>
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
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr>
                        <td class="k">Références</td>
                        <td class="v">{{ $lines->count() }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="k">TOTAL SORTI</td>
                        <td class="v">{{ $lines->sum('quantity') }} unité{{ $lines->sum('quantity') > 1 ? 's' : '' }}</td>
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
