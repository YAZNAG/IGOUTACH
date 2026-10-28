@extends('pdf.layouts.document')

@section('title', $sale->reference)

@section('document_title')
    @if ($sale->type === 'quote')
        <span class="a">DE</span><span class="b">VIS</span>
    @elseif ($sale->status === 'draft')
        {{-- Tant qu'elle n'est pas confirmée, la vente n'a rien sorti du stock
             ni engagé de créance : la nommer « facture » donnerait au client un
             document qui n'en est pas un. C'est un bon, révisable. --}}
        <span class="a">B</span><span class="b">ON</span>
    @else
        <span class="a">FAC</span><span class="b">TURE</span>
    @endif
@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $sale->reference }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y à H:i') ?? '—' }}</td></tr>
        @php($etabli = $sale->created_at?->format('d/m/Y à H:i'))
        @php($affiche = ($sale->confirmed_at ?? $sale->created_at)?->format('d/m/Y à H:i'))
        {{-- La plupart des ventes sont confirmees dans la minute : repeter le
             meme horodatage sur deux lignes n'apprend rien. On ne montre la
             date de saisie que lorsqu'elle differe de la date du document. --}}
        @if ($etabli !== null && $etabli !== $affiche)
        <tr><td class="k">Établi le</td><td class="v">{{ $etabli }}</td></tr>
        @endif
        <tr><td class="k">Statut</td><td class="v">{{ $sale->status === 'confirmed' ? 'Confirmée' : ($sale->status === 'cancelled' ? 'Annulée' : 'Brouillon') }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Client --}}
    <table class="address-table address-solo">
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
    </table>

    {{-- Totaux : bloc compact aligné à droite, comme sur le modèle. --}}
    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr>
                        <td class="k">Sous-total</td>
                        <td class="v">{{ number_format((float) $sale->subtotal, 2, ',', ' ') }} DH</td>
                    </tr>
                    @php
                        $tva = (float) $sale->tax_amount;
                        $ht = round((float) $sale->total - $tva, 2);
                        // Sans TVA, la remise se lit sur le total : avec, elle
                        // se lit sur le hors taxe, sans quoi le rabais semble
                        // porter sur une somme qui inclut la taxe.
                        $remise = round((float) $sale->subtotal - $ht, 2);
                    @endphp
                    @if ((float) $sale->discount_percent > 0)
                        <tr>
                            <td class="k">Remise ({{ number_format((float) $sale->discount_percent, 1, ',', ' ') }} %)</td>
                            <td class="v">-{{ number_format($remise, 2, ',', ' ') }} DH</td>
                        </tr>
                    @endif
                    {{-- Un lieu soumis à TVA facture en trois lignes : la base,
                         la taxe, puis ce que le client paie. Les autres lieux
                         gardent leur unique total. --}}
                    @if ($tva > 0)
                        <tr>
                            <td class="k">Total HT</td>
                            <td class="v">{{ number_format($ht, 2, ',', ' ') }} DH</td>
                        </tr>
                        <tr>
                            <td class="k">TVA ({{ number_format((float) $sale->tax_rate, 0, ',', ' ') }} %)</td>
                            <td class="v">{{ number_format($tva, 2, ',', ' ') }} DH</td>
                        </tr>
                        <tr class="grand">
                            <td class="k">TOTAL TTC</td>
                            <td class="v">{{ number_format((float) $sale->total, 2, ',', ' ') }} DH</td>
                        </tr>
                    @else
                        <tr class="grand">
                            <td class="k">TOTAL</td>
                            <td class="v">{{ number_format((float) $sale->total, 2, ',', ' ') }} DH</td>
                        </tr>
                    @endif
                    @if ((float) $sale->paid_amount > 0)
                        <tr>
                            <td class="k">Payé</td>
                            <td class="v">{{ number_format((float) $sale->paid_amount, 2, ',', ' ') }} DH</td>
                        </tr>
                        <tr>
                            <td class="k">Reste à payer</td>
                            <td class="v">{{ number_format(max(0, (float) $sale->total - (float) $sale->paid_amount), 2, ',', ' ') }} DH</td>
                        </tr>
                    @endif
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
