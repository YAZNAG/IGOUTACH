@extends('pdf.layouts.document')

@section('title', $transfer->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">TRANSFERT</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $transfer->reference }}</td></tr>
        <tr><td class="k">État</td><td class="v">{{ $transfer->status?->name ?? '—' }}</td></tr>
        @php
            $cree = $transfer->created_at?->format('d/m/Y à H:i');
        @endphp
        {{-- Un transfert expedie dans la foulee de sa creation porterait deux
             fois le meme horodatage : on ne l'affiche que s'il apporte une
             information. --}}
        @if ($cree !== null && $cree !== $transfer->sent_at?->format('d/m/Y à H:i'))
        <tr><td class="k">Créé le</td><td class="v">{{ $cree }}</td></tr>
        @endif
        <tr><td class="k">Expédié le</td><td class="v">{{ $transfer->sent_at?->format('d/m/Y à H:i') ?? '—' }}</td></tr>
        <tr><td class="k">Reçu le</td><td class="v">{{ $transfer->received_at?->format('d/m/Y à H:i') ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
    {{-- Mise en forme propre au bon de transfert : lignes resserrees (un bon de
         cinquante articles tenait sur quatre pages), numero de ligne pour le
         pointage au telephone, reference sur une ligne, zebrage leger. --}}
    <style>
        table.lines.tr thead th { padding: 5px 6px; }
        table.lines.tr tbody td { padding: 3px 6px; font-size: 8.5pt; line-height: 1.25; }
        table.lines.tr tbody tr:nth-child(even) td { background-color: #F7F8F9; }
        table.lines.tr td.no { color: #9AA0A6; text-align: center; font-size: 7.5pt; }
        table.lines.tr td.ref { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.5pt; color: #3C4046; }
        table.lines.tr td.qte { font-weight: bold; }
        table.lines.tr td.coche { text-align: center; color: #9AA0A6; }
        table.lines.tr td.ecart-neg { color: #B42318; font-weight: bold; }
        table.lines.tr td.ecart-pos { color: #1F7A3A; font-weight: bold; }
        .tr-bandeau { width: 100%; border-collapse: collapse; margin: 0 0 10px 0; }
        .tr-bandeau td { background-color: #141414; color: #FFFFFF; padding: 6px 12px; font-size: 9pt; }
        .tr-bandeau td.d { text-align: right; }
    </style>

    <table class="tr-bandeau">
        <tr>
            <td><strong>{{ $transfer->fromWarehouse?->code }}</strong> &nbsp;→&nbsp; <strong>{{ $transfer->toWarehouse?->code }}</strong></td>
            <td class="d">{{ $lines->count() }} article(s) · {{ $totalEnvoye }} unité(s) envoyée(s)</td>
        </tr>
    </table>

    {{-- Lieu d'origine / lieu de destination --}}
    <table class="address-table">
        <tr>
            <td class="address-box">
                <div class="label">Expédié depuis</div>
                <div class="name">{{ $transfer->fromWarehouse?->code }} — {{ $transfer->fromWarehouse?->name }}</div>
                <div class="detail">
                    @if ($transfer->fromWarehouse?->address)
                        {{ $transfer->fromWarehouse->address }}<br>
                    @endif
                    @if ($transfer->fromWarehouse?->city)
                        {{ $transfer->fromWarehouse->city }}
                    @endif
                </div>
            </td>
            <td class="address-box">
                <div class="label">Destiné à</div>
                <div class="name">{{ $transfer->toWarehouse?->code }} — {{ $transfer->toWarehouse?->name }}</div>
                <div class="detail">
                    @if ($transfer->toWarehouse?->address)
                        {{ $transfer->toWarehouse->address }}<br>
                    @endif
                    @if ($transfer->toWarehouse?->city)
                        {{ $transfer->toWarehouse->city }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes. Les colonnes « Reçu » et « Écart » n'apparaissent qu'une fois
         la réception saisie : vides, elles laisseraient croire à un manquant. --}}
    {{-- Sans reception saisie, une colonne « Reçu » vide et une case de
         pointage restent a remplir a la main par le lieu destinataire. --}}
    <table class="lines tr">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">N°</th>
                <th style="width: 22%;">Réf</th>
                <th>Désignation</th>
                <th class="num" style="width: 10%;">Envoyé</th>
                <th class="num" style="width: 10%;">Reçu</th>
                @if ($receptionSaisie)
                    <th class="num" style="width: 9%;">Écart</th>
                @else
                    <th style="width: 7%; text-align: center;">✓</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                @php
                    $recu = $line->quantity_received;
                    $ecart = $recu === null ? null : (int) $recu - (int) $line->quantity_sent;
                @endphp
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td class="ref">{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num qte">{{ $line->quantity_sent }}</td>
                    <td class="num">{{ $receptionSaisie ? ($recu ?? '—') : '' }}</td>
                    @if ($receptionSaisie)
                        <td class="num {{ $ecart !== null && $ecart < 0 ? 'ecart-neg' : ($ecart > 0 ? 'ecart-pos' : '') }}">
                            @if ($ecart === null)
                                —
                            @else
                                {{ $ecart > 0 ? '+'.$ecart : $ecart }}
                            @endif
                        </td>
                    @else
                        <td class="coche">☐</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr>
                        <td class="k">Lignes</td>
                        <td class="v">{{ $lines->count() }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="k">TOTAL ENVOYÉ</td>
                        <td class="v">{{ $totalEnvoye }}</td>
                    </tr>
                    @if ($receptionSaisie)
                        <tr class="grand">
                            <td class="k">TOTAL REÇU</td>
                            <td class="v">{{ $totalRecu }}</td>
                        </tr>
                        @if ($totalRecu !== $totalEnvoye)
                            <tr class="grand">
                                <td class="k">ÉCART</td>
                                <td class="v">{{ $totalRecu - $totalEnvoye > 0 ? '+' : '' }}{{ $totalRecu - $totalEnvoye }}</td>
                            </tr>
                        @endif
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Suivi : qui a fait quoi. Utile quand le bon revient signé et qu'il
         faut retrouver l'interlocuteur de chaque étape. --}}
    <div class="notes-box">
        <div class="label">Suivi</div>
        <div>
            Créé par : {{ $noms[$transfer->getAttribute('created_by')] ?? '—' }}
            @if ($transfer->getAttribute('approved_by'))
                &nbsp;·&nbsp; Approuvé par : {{ $noms[$transfer->getAttribute('approved_by')] ?? '—' }}
            @endif
            @if ($transfer->getAttribute('received_by'))
                &nbsp;·&nbsp; Réceptionné par : {{ $noms[$transfer->getAttribute('received_by')] ?? '—' }}
            @endif
            @if ($transfer->note)
                <br>Note : {{ $transfer->note }}
            @endif
            @if ($transfer->refusal_reason)
                <br>Motif de refus : {{ $transfer->refusal_reason }}
            @endif
        </div>
    </div>

    {{-- Signatures : le bon accompagne la marchandise et revient signé. --}}
    <table class="signature-table" style="page-break-inside: avoid;">
        <tr>
            <td class="signature-box" style="width: 33%;">
                <div class="label">Expéditeur</div>
                <div class="muted" style="font-size: 7.5pt;">{{ $transfer->fromWarehouse?->code }}</div>
            </td>
            <td class="signature-box" style="width: 33%;">
                <div class="label">Transporteur</div>
            </td>
            <td class="signature-box" style="width: 33%;">
                <div class="label">Réceptionnaire</div>
                <div class="muted" style="font-size: 7.5pt;">{{ $transfer->toWarehouse?->code }}</div>
            </td>
        </tr>
    </table>
@endsection
