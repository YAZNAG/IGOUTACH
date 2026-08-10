@extends('pdf.layouts.document')

@section('title', $transfer->reference)

@section('document_title')<span class="a">BON DE </span><span class="b">TRANSFERT</span>@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">N°</td><td class="v">{{ $transfer->reference }}</td></tr>
        <tr><td class="k">État</td><td class="v">{{ $transfer->status?->name ?? '—' }}</td></tr>
        <tr><td class="k">Expédié le</td><td class="v">{{ $transfer->sent_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="k">Reçu le</td><td class="v">{{ $transfer->received_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>
@endsection

@section('content')
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
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 16%;">Réf</th>
                <th>Désignation</th>
                <th class="num" style="width: 12%;">Envoyé</th>
                @if ($receptionSaisie)
                    <th class="num" style="width: 12%;">Reçu</th>
                    <th class="num" style="width: 12%;">Écart</th>
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
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td class="num">{{ $line->quantity_sent }}</td>
                    @if ($receptionSaisie)
                        <td class="num">{{ $recu ?? '—' }}</td>
                        <td class="num">
                            @if ($ecart === null)
                                —
                            @elseif ($ecart === 0)
                                0
                            @else
                                <strong>{{ $ecart > 0 ? '+'.$ecart : $ecart }}</strong>
                            @endif
                        </td>
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
@endsection
