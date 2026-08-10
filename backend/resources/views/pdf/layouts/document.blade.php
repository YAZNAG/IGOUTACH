<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    @php
        /* Identité légale, regroupée ici : elle se répète dans l'en-tête et le
           pied, et la corriger à deux endroits finirait par produire deux
           versions divergentes. Reprise de la facture officielle IGOUTECH. */
        $societe = [
            'raison' => 'STE IGOUTECH S.A.R.L.',
            'adresse' => 'N 82 BIS HAY EL GHAYATEN AV ESSAADYINE DCHEIRA EL JIHADIA (M)',
            'ice' => '003519480000090',
            'if' => '66007361',
            'tp' => '49706001',
            'gsm' => '0661341783 / 0526455552',
            'email' => 'igoutechsarl@gmail.com',
        ];
    @endphp
    <style>
        /* Document monochrome : le logo est la seule pièce en couleur, tout le
           reste est en gris et noir. Un document commercial gagne à laisser la
           couleur à l'identité et rien qu'à elle — les aplats colorés brouillent
           la lecture et coûtent cher à l'impression.

           Mise en page reprise du modèle de facture fourni.

           L'en-tête est en flux normal et NON en position fixe : avec DomPDF,
           un bloc fixé en coordonnées négatives sortait de la page — logo,
           coordonnées et titre étaient purement invisibles sur les documents
           générés. Le pied reste fixe, en coordonnées positives.

           Les marges sont portées par .sheet et non par @page : cette version
           de DomPDF ignore la marge de @page, et le contenu se retrouvait
           collé au bord du papier, donc rogné à l'impression. */
        @page { margin: 0; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Après le reset, sans quoi il annulerait ce padding. La réserve du bas
           doit dépasser la hauteur du pied fixe, sinon le contenu passe dessous. */
        .sheet { padding: 30px 34px 72px 34px; }

        body {
            font-family: 'DejaVu Sans', Helvetica, sans-serif;
            font-size: 9pt;
            color: #141414;
            line-height: 1.45;
        }

        /* ---- En-tête ---- */
        .doc-header { width: 100%; margin-bottom: 4px; }
        .doc-header td { vertical-align: top; }

        .company-tag { font-size: 8.5pt; color: #141414; text-align: right; font-weight: bold; }
        .company-details { font-size: 7.5pt; color: #5C6169; text-align: right; line-height: 1.6; }

        /* Filet sous l'en-tête : segment appuyé puis trait clair. */
        .rule { width: 100%; border-collapse: collapse; margin: 10px 0 14px 0; }
        .rule td { height: 2.4pt; padding: 0; font-size: 0; line-height: 0; }
        .rule .accent { background-color: #141414; width: 88px; }
        .rule .rest { background-color: #E4E5E8; height: 0.8pt; }

        /* ---- Titre + références ---- */
        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .title-row td { vertical-align: bottom; }

        .doc-title { font-size: 21pt; font-weight: bold; letter-spacing: -0.5px; line-height: 1.1; }
        .doc-title .a { color: #141414; }
        .doc-title .b { color: #5C6169; }

        .meta { border-collapse: collapse; margin-left: auto; }
        .meta td { padding: 1px 0 1px 12px; font-size: 8.5pt; }
        .meta .k {
            color: #5C6169;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: right;
            white-space: nowrap;
        }
        .meta .v { color: #141414; font-weight: bold; text-align: right; white-space: nowrap; }

        /* ---- Blocs adresses ---- */
        .address-table { width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 10px 0; }
        /* Un seul interlocuteur sur le document : la cellule prend toute la
           largeur, sinon elle laisse une demi-page vide à sa droite. */
        .address-table.address-solo .address-box { width: 100%; }
        .address-box {
            width: 50%;
            background-color: #F7F8F9;
            border-left: 3px solid #9AA0A6;
            padding: 9px 12px;
            vertical-align: top;
        }
        .address-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5C6169;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .address-box .name { font-weight: bold; color: #141414; font-size: 10pt; }
        .address-box .detail { font-size: 8pt; color: #5C6169; line-height: 1.55; }

        /* ---- Tableau des lignes ---- */
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 0; }

        /* Cellules entièrement bordées, comme sur le modèle fourni : sur un bon
           rempli à la main après impression, un simple filet sous la ligne ne
           suffit pas à séparer les colonnes. */
        table.lines thead th {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #FFFFFF;
            background-color: #595F66;
            border: 0.5pt solid #595F66;
            padding: 7px 8px;
            text-align: left;
        }

        table.lines tbody td {
            padding: 6px 8px;
            border: 0.5pt solid #C9CDD2;
            font-size: 9pt;
        }

        table.lines .num { text-align: right; }
        table.lines .center { text-align: center; }

        /* ---- Totaux : bloc compact à droite, pas un bandeau pleine largeur ---- */
        .totals-wrap { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .totals { border-collapse: collapse; margin-left: auto; width: 47%; }
        .totals td { padding: 5px 10px; font-size: 9pt; }
        .totals .k { text-align: right; color: #5C6169; }
        .totals .v { text-align: right; font-weight: bold; white-space: nowrap; }
        /* Même gris que l'en-tête du tableau : deux aplats différents feraient
           croire à deux niveaux de lecture là où il n'y en a qu'un. */
        .totals tr.grand td {
            background-color: #595F66;
            color: #FFFFFF;
            font-size: 10.5pt;
            font-weight: bold;
            padding: 8px 10px;
        }
        .totals tr.grand td.k { color: #FFFFFF; }

        /* ---- Notes ---- */
        .notes-box { margin-top: 18px; border: 0.5pt solid #E4E5E8; padding: 9px 12px; min-height: 42px; }
        .notes-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5C6169;
            font-weight: bold;
            margin-bottom: 4px;
        }

        /* ---- Signatures ---- */
        .signature-table { width: 100%; margin-top: 24px; border-collapse: separate; border-spacing: 10px 0; }
        .signature-box { width: 50%; border: 0.5pt solid #E4E5E8; height: 76px; padding: 7px 12px; vertical-align: top; }
        .signature-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5C6169;
            font-weight: bold;
        }

        /* ---- Pied de page ---- */
        .doc-footer {
            position: fixed;
            bottom: 16px;
            left: 34px;
            right: 34px;
            height: 42px;
            font-size: 6.5pt;
            color: #5C6169;
            border-top: 0.8pt solid #141414;
            padding-top: 6px;
            text-align: center;
            line-height: 1.5;
        }
        .doc-footer .sep { color: #9AA0A6; }
        .doc-footer .legal { display: block; }
        /* Numéro seul : DomPDF renvoie 0 pour counter(pages), ce qui affichait
           « Page 1 / 0 ». Mieux vaut pas de total qu'un total faux. */
        .doc-footer .pagenum:after { content: "Page " counter(page); }

        .muted { color: #5C6169; }

        /* ---- Invocation de bas de document ---- */
        /* Bloc volontairement compact : plus haut, il basculait seul sur une
           deuxieme page et faisait imprimer une feuille pour une ligne. */
        .doua {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 0.8pt solid #E4E5E8;
            text-align: center;
            page-break-inside: avoid;
        }
        .doua img { height: 21px; }
    </style>
</head>
<body>
    {{-- Mentions légales : elles doivent figurer sur chaque page, d'où le pied
         fixe plutôt qu'un bloc en fin de contenu. --}}
    <div class="doc-footer">
        <table style="width: 100%; margin-bottom: 2px;">
            <tr>
                <td style="width: 50%;">&nbsp;</td>
                <td style="text-align: right; width: 50%;"><span class="pagenum"></span></td>
            </tr>
        </table>
        <span class="legal">
            {{ $societe['raison'] }} &nbsp;S.Social : {{ $societe['adresse'] }},
            ICE : {{ $societe['ice'] }} , IF : {{ $societe['if'] }} , TP : {{ $societe['tp'] }}
        </span>
        <span class="legal">
            GSM : {{ $societe['gsm'] }} &nbsp;<span class="sep">&#9670;</span>&nbsp;
            Email : {{ $societe['email'] }}
        </span>
    </div>

    <div class="sheet">
    <table class="doc-header">
        <tr>
            <td style="width: 45%;">
                {{-- Logo en image : DomPDF ne rend pas le SVG de façon fiable. --}}
                <img src="{{ public_path('images/igoutech-logo.png') }}" alt="iGouTech" style="height: 44px;">
            </td>
            <td style="width: 55%;">
                <div class="company-tag">{{ $societe['raison'] }}</div>
                <div class="company-details">
                    {{ $societe['adresse'] }}<br>
                    {{ $societe['gsm'] }}<br>
                    {{ $societe['email'] }}
                </div>
            </td>
        </tr>
    </table>

    <table class="rule">
        <tr>
            <td class="accent"></td>
            <td class="rest"></td>
        </tr>
    </table>

    <table class="title-row">
        <tr>
            <td style="width: 50%;">
                <div class="doc-title">@yield('document_title')</div>
            </td>
            <td style="width: 50%;">
                @yield('document_meta')
            </td>
        </tr>
    </table>

    @yield('content')

    {{-- Invocation, au bas de chaque document.

         Rendue en image : DomPDF n'assemble pas les lettres arabes et ignore
         le sens droite-à-gauche, ce qui donnerait des caractères isolés lus à
         l'envers. L'image `public/images/doua.png` est composée une fois pour
         toutes avec une police arabe et la mise en forme adéquate ; la
         modifier suppose de la recomposer, pas d'éditer ce gabarit. --}}
    @php($doua = public_path('images/doua.png'))
    @if (file_exists($doua))
        <div class="doua">
            <img src="{{ $doua }}" alt="اللهم بارك لنا في تجارتنا">
        </div>
    @endif
    </div>
</body>
</html>
