@extends('pdf.layouts.document')

@section('title', $payment->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">RÈGLEMENT</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $payment->reference }}</td></tr>
        <tr><td class="k">Date du règlement</td><td class="v">{{ $payment->received_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="k">Mode</td><td class="v">{{ $payment->method?->name ?? '—' }}</td></tr>
        @if ($payment->cheque_reference)
        <tr><td class="k">N° de l'effet</td><td class="v">{{ $payment->cheque_reference }}</td></tr>
        @endif
        <tr><td class="k">Encaissé par</td><td class="v">{{ $encaissePar ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Mise en forme propre au bon de règlement : il tient en une page et se
         lit d'un coup d'œil — le montant reçu, puis ce qu'il solde. --}}
    <style>
        .recu { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .recu td { background-color: #141414; color: #FFFFFF; padding: 10px 14px; }
        .recu .l { font-size: 8pt; letter-spacing: 1.2px; text-transform: uppercase; color: #C9CDD2; }
        .recu .m { font-size: 17pt; font-weight: bold; text-align: right; }
        .lettres { font-size: 8.5pt; color: #5C6169; font-style: italic; margin-bottom: 14px; }
        table.lines.pay tbody td { padding: 5px 8px; font-size: 8.5pt; }
        table.lines.pay tbody tr:nth-child(even) td { background-color: #F7F8F9; }
        table.lines.pay td.ref { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.5pt; }
        .solde { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .solde td { padding: 6px 10px; font-size: 9pt; border: 0.5pt solid #E4E5E8; }
        .solde .k { color: #5C6169; width: 60%; }
        .solde .v { text-align: right; font-weight: bold; white-space: nowrap; }
        .solde tr.fin td { background-color: #595F66; color: #FFFFFF; font-size: 10.5pt; }
    </style>

    <table class="address-table address-solo">
        <tr>
            <td class="address-box">
                <div class="label">Reçu de</div>
                <div class="name">{{ $payment->customer?->name ?? '—' }}</div>
                <div class="detail">
                    @if ($payment->customer?->code)
                        Code : {{ $payment->customer->code }}<br>
                    @endif
                    @if ($payment->customer?->phone)
                        Tél : {{ $payment->customer->phone }}<br>
                    @endif
                    @if ($payment->customer?->address)
                        {{ $payment->customer->address }}@if ($payment->customer?->city), {{ $payment->customer->city }}@endif
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="recu">
        <tr>
            <td class="l">Montant reçu</td>
            <td class="m">{{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
        </tr>
    </table>

    @if ($enLettres !== null)
        <div class="lettres">Arrêté le présent bon à la somme de {{ $enLettres }}.</div>
    @endif

    {{-- Ce que le versement solde, facture par facture. Sans ventilation, il
         s'impute sur l'encours : le dire évite de chercher une liste absente. --}}
    @if (count($affectations) > 0)
        <table class="lines pay">
            <thead>
                <tr>
                    <th style="width: 22%;">Facture</th>
                    <th style="width: 15%;">Date</th>
                    <th class="num">Total (DH)</th>
                    <th class="num">Réglé ici (DH)</th>
                    <th class="num">Reste dû (DH)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($affectations as $a)
                    <tr>
                        <td class="ref">{{ $a['reference'] }}</td>
                        <td>{{ $a['date'] }}</td>
                        <td class="num">{{ number_format($a['total'], 2, ',', ' ') }}</td>
                        <td class="num"><strong>{{ number_format($a['paye'], 2, ',', ' ') }}</strong></td>
                        <td class="num">{{ number_format($a['reste'], 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="lettres">
            Ce règlement s'impute sur l'encours du compte, sans affectation à une facture précise.
        </div>
    @endif

    <table class="solde">
        <tr>
            <td class="k">Encours du compte avant ce règlement</td>
            <td class="v">{{ number_format($soldeAvant, 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <td class="k">Montant réglé</td>
            <td class="v">− {{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
        </tr>
        <tr class="fin">
            <td class="k" style="color: #FFFFFF;">RESTE DÛ APRÈS CE RÈGLEMENT</td>
            <td class="v">{{ number_format($soldeApres, 2, ',', ' ') }} DH</td>
        </tr>
    </table>

    @if ($payment->note)
        <div class="notes-box">
            <div class="label">Note</div>
            <div>{{ $payment->note }}</div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="label">Le client</div>
            </td>
            {{-- L'identité de la société est posée par le gabarit, dans son
                 en-tête : la reprendre ici obligerait à la maintenir deux fois. --}}
            <td class="signature-box">
                <div class="label">Le caissier</div>
            </td>
        </tr>
    </table>
@endsection
