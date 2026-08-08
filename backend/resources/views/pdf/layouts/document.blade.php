<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        /* Charte iGouTech : rouge #EE1B0F, encre #141414.
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

        /* Après le reset, sans quoi il annulerait ce padding. */
        .sheet { padding: 30px 34px 52px 34px; }

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

        /* Filet sous l'en-tête : segment rouge appuyé puis trait clair. */
        .rule { width: 100%; border-collapse: collapse; margin: 10px 0 14px 0; }
        .rule td { height: 2.4pt; padding: 0; font-size: 0; line-height: 0; }
        .rule .accent { background-color: #EE1B0F; width: 88px; }
        .rule .rest { background-color: #E4E5E8; height: 0.8pt; }

        /* ---- Titre + références ---- */
        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .title-row td { vertical-align: bottom; }

        .doc-title { font-size: 21pt; font-weight: bold; letter-spacing: -0.5px; line-height: 1.1; }
        .doc-title .a { color: #141414; }
        .doc-title .b { color: #EE1B0F; }

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
        .address-box {
            width: 50%;
            background-color: #F7F8F9;
            border-left: 3px solid #EE1B0F;
            padding: 9px 12px;
            vertical-align: top;
        }
        .address-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #EE1B0F;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .address-box .name { font-weight: bold; color: #141414; font-size: 10pt; }
        .address-box .detail { font-size: 8pt; color: #5C6169; line-height: 1.55; }

        /* ---- Tableau des lignes ---- */
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 0; }

        table.lines thead th {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #FFFFFF;
            background-color: #141414;
            padding: 8px 9px;
            text-align: left;
        }

        table.lines tbody td {
            padding: 7px 9px;
            border-bottom: 0.5pt solid #E4E5E8;
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
        .totals tr.grand td {
            background-color: #141414;
            color: #FFFFFF;
            font-size: 11pt;
            font-weight: bold;
            padding: 9px 10px;
        }
        .totals tr.grand td.k { color: #FFFFFF; border-left: 3px solid #EE1B0F; }

        /* ---- Notes ---- */
        .notes-box { margin-top: 18px; border: 0.5pt solid #E4E5E8; padding: 9px 12px; min-height: 42px; }
        .notes-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #EE1B0F;
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
            bottom: 14px;
            left: 34px;
            right: 34px;
            height: 30px;
            font-size: 7.5pt;
            color: #5C6169;
            border-top: 0.8pt solid #E4E5E8;
            padding-top: 7px;
        }
        .doc-footer .sep { color: #EE1B0F; }
        /* Numéro seul : DomPDF renvoie 0 pour counter(pages), ce qui affichait
           « Page 1 / 0 ». Mieux vaut pas de total qu'un total faux. */
        .doc-footer .pagenum:after { content: "Page " counter(page); }

        .muted { color: #5C6169; }
    </style>
</head>
<body>
    <div class="doc-footer">
        <table style="width: 100%;">
            <tr>
                <td style="width: 22%;">&nbsp;</td>
                <td style="text-align: center; width: 56%;">
                    Inzegane — Agadir <span class="sep">&#9670;</span> 0661 24 17 83
                    <span class="sep">&#9670;</span> 0528 83 88 46
                </td>
                <td style="text-align: right; width: 22%;"><span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <div class="sheet">
    <table class="doc-header">
        <tr>
            <td style="width: 45%;">
                {{-- Logo en image : DomPDF ne rend pas le SVG de façon fiable. --}}
                <img src="{{ public_path('images/igoutech-logo.png') }}" alt="iGouTech" style="height: 44px;">
            </td>
            <td style="width: 55%;">
                <div class="company-tag">Solutions informatiques &amp; services numériques</div>
                <div class="company-details">
                    Inzegane — Agadir, Maroc<br>
                    0661 24 17 83 &nbsp;&nbsp;|&nbsp;&nbsp; 0528 83 88 46<br>
                    contact&#64;igoutech.ma
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
    </div>
</body>
</html>
