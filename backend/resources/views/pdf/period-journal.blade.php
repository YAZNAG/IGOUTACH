@extends('pdf.layouts.document')

@section('title', $titre)

@section('document_title')
    @php
        $mots = explode(' ', $titre, 2);
    @endphp
    <span class="a">{{ $mots[0] }} </span><span class="b">{{ strtoupper($mots[1] ?? '') }}</span>
@endsection

@section('document_meta')
    <table class="meta">
        <tr><td class="k">Période</td><td class="v">{{ $du }} → {{ $au }}</td></tr>
        <tr><td class="k">Lieu</td><td class="v">{{ $lieu }}</td></tr>
        <tr><td class="k">Lignes</td><td class="v">{{ count($lignes) }}</td></tr>
    </table>
@endsection

@section('content')
    <table class="lines">
        <thead>
            <tr>
                @foreach ($entetes as $i => $entete)
                    <th @class(['num' => $i === count($entetes) - 1])
                        @if ($i === 0) style="width: 11%;" @endif>{{ $entete }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['date'] }}</td>
                    <td class="mono">{{ $ligne['reference'] }}</td>
                    <td>{{ $ligne['tiers'] }}</td>
                    <td class="muted">{{ $ligne['detail'] }}</td>
                    <td class="mono">{{ $ligne['lieu'] }}</td>
                    <td class="num">
                        {{ number_format((float) $ligne['montant'], $unites ? 0 : 2, ',', ' ') }}
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- Un journal vide se dit : sans cette ligne, on croirait
                         à un document tronqué. --}}
                    <td colspan="{{ count($entetes) }}" style="text-align: center; padding: 24px; color: #5C6169;">
                        Aucun mouvement sur cette période.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (count($lignes) > 0)
        <table class="totals-wrap">
            <tr>
                <td>
                    <table class="totals">
                        <tr class="grand">
                            <td class="k">TOTAL</td>
                            <td class="v">
                                {{ number_format((float) $total, $unites ? 0 : 2, ',', ' ') }}
                                {{ $unites ? 'unités' : 'DH' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="k">Nombre de lignes</td>
                            <td class="v">{{ count($lignes) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif
@endsection
