@extends('pdf.layouts.document')

@section('title', 'Relevé de crédit — '.$client['name'])

@section('document_title')<span class="a">RELEVÉ DE </span><span class="b">CRÉDIT</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">Édité le</td><td class="v">{{ $edite_le }}</td></tr>
        <tr><td class="k">Client</td><td class="v">{{ $client['code'] ?? '—' }}</td></tr>
        @if ($lieu !== null)
        <tr><td class="k">Lieu</td><td class="v">{{ $lieu }}</td></tr>
        @endif
        <tr><td class="k">Factures dues</td><td class="v">{{ count($factures) }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Le relevé se lit en deux temps : d'abord le chiffre qu'on réclame,
         puis, facture par facture, les articles qui l'expliquent. --}}
    <style>
        .du { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .du td { background-color: #141414; color: #FFFFFF; padding: 10px 14px; }
        .du .l { font-size: 8pt; letter-spacing: 1.2px; text-transform: uppercase; color: #C9CDD2; }
        .du .m { font-size: 17pt; font-weight: bold; text-align: right; }
        .resume { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .resume td { padding: 5px 10px; font-size: 8.5pt; border: 0.5pt solid #E4E5E8; }
        .resume .k { color: #5C6169; }
        .resume .v { text-align: right; font-weight: bold; white-space: nowrap; }
        .resume .warn { color: #8A4B00; }
        .resume .bad { color: #9E1B14; }
        .facture { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
        .facture td.titre { background-color: #595F66; color: #FFFFFF; padding: 6px 10px; font-size: 9pt; font-weight: bold; }
        .facture td.titre .r { float: right; font-weight: normal; }
        table.lines.art tbody td { padding: 4px 8px; font-size: 8.5pt; }
        table.lines.art tbody tr:nth-child(even) td { background-color: #F7F8F9; }
        table.lines.art td.ref { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.5pt; }
        table.lines.art tfoot td { padding: 5px 8px; font-size: 8.5pt; border-top: 0.5pt solid #C9CDD2; }
        table.lines.art tfoot td.num { font-weight: bold; }
        table.lines.art tfoot tr.reste td { background-color: #F1F2F4; }
        .regle { font-size: 8pt; color: #5C6169; padding: 4px 8px 8px 8px; }
        .vide { font-size: 9pt; color: #5C6169; font-style: italic; margin: 14px 0; }
    </style>

    <table class="address-table address-solo">
        <tr>
            <td class="address-box">
                <div class="label">Client</div>
                <div class="name">{{ $client['name'] }}</div>
                <div class="detail">
                    @if ($client['code'])
                        Code : {{ $client['code'] }}<br>
                    @endif
                    @if ($client['phone'])
                        Tél : {{ $client['phone'] }}<br>
                    @endif
                    @if ($client['address'])
                        {{ $client['address'] }}@if ($client['city']), {{ $client['city'] }}@endif<br>
                    @endif
                    @if ($client['credit_limit'] > 0)
                        Plafond de crédit : {{ number_format($client['credit_limit'], 2, ',', ' ') }} DH
                    @endif
                    @if ($client['is_blocked'])
                        — <strong>compte bloqué</strong>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="du">
        <tr>
            <td class="l">Reste dû à ce jour</td>
            <td class="m">{{ number_format($totaux['du'], 2, ',', ' ') }} DH</td>
        </tr>
    </table>

    <table class="resume">
        <tr>
            <td class="k">Total des factures dues</td>
            <td class="v">{{ number_format($totaux['facture'], 2, ',', ' ') }} DH</td>
            <td class="k">0 – 30 jours</td>
            <td class="v">{{ number_format($anciennete['j0_30'], 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <td class="k">Déjà réglé sur ces factures</td>
            <td class="v">− {{ number_format($totaux['paye'], 2, ',', ' ') }} DH</td>
            <td class="k">31 – 60 jours</td>
            <td class="v">{{ number_format($anciennete['j31_60'], 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <td class="k">Articles concernés</td>
            <td class="v">{{ $totaux['articles'] }} ligne(s), {{ $totaux['unites'] }} unité(s)</td>
            <td class="k">61 – 90 jours</td>
            <td class="v warn">{{ number_format($anciennete['j61_90'], 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <td class="k">Répartition par lieu</td>
            <td class="v">
                @forelse ($par_lieu as $l)
                    {{ $l['code'] }} : {{ number_format($l['du'], 2, ',', ' ') }}@if (! $loop->last) · @endif
                @empty
                    —
                @endforelse
            </td>
            <td class="k">Plus de 90 jours</td>
            <td class="v bad">{{ number_format($anciennete['plus_90'], 2, ',', ' ') }} DH</td>
        </tr>
    </table>

    @forelse ($factures as $f)
        <table class="facture">
            <tr>
                <td class="titre">
                    Facture {{ $f['reference'] }} — {{ $f['date'] }}
                    <span class="r">{{ $f['lieu'] }} · {{ $f['age'] }} jour(s)</span>
                </td>
            </tr>
        </table>
        <table class="lines art">
            <thead>
                <tr>
                    <th style="width: 16%;">Référence</th>
                    <th>Article</th>
                    <th class="num" style="width: 9%;">Qté</th>
                    <th class="num" style="width: 15%;">P.U. (DH)</th>
                    <th class="num" style="width: 16%;">Total (DH)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($f['lignes'] as $l)
                    <tr>
                        <td class="ref">{{ $l['sku'] }}</td>
                        <td>{{ $l['nom'] }}</td>
                        <td class="num">{{ $l['quantite'] }}</td>
                        <td class="num">{{ number_format($l['pu'], 2, ',', ' ') }}</td>
                        <td class="num">{{ number_format($l['total'], 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="num" style="color: #5C6169; font-weight: normal;">Total facture</td>
                    <td class="num">{{ number_format($f['total'], 2, ',', ' ') }}</td>
                </tr>
                @if ($f['paye'] > 0)
                <tr>
                    <td colspan="4" class="num" style="color: #5C6169; font-weight: normal;">Déjà réglé</td>
                    <td class="num">− {{ number_format($f['paye'], 2, ',', ' ') }}</td>
                </tr>
                @endif
                <tr class="reste">
                    <td colspan="4" class="num">Reste dû sur cette facture</td>
                    <td class="num">{{ number_format($f['reste'], 2, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
        @if (count($f['reglements']) > 0)
            <div class="regle">
                Règlements reçus :
                @foreach ($f['reglements'] as $r)
                    {{ $r['reference'] }} le {{ $r['date'] }} ({{ number_format($r['montant'], 2, ',', ' ') }} DH)@if (! $loop->last),@endif
                @endforeach
            </div>
        @endif
    @empty
        <div class="vide">Aucune facture en attente de règlement pour ce client.</div>
    @endforelse

    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="label">Le client</div>
            </td>
            <td class="signature-box">
                <div class="label">Pour la société</div>
            </td>
        </tr>
    </table>
@endsection
