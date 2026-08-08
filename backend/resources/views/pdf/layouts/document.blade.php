<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        /* Charte iGouTech : rouge #EE1B0F, encre #141414.
           Mise en page reprise du modèle de facture fourni. */
        @page { margin: 158px 40px 62px 40px; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Helvetica, sans-serif;
            font-size: 9pt;
            color: #141414;
            line-height: 1.45;
        }

        /* ---- En-tête fixe ---- */
        .doc-header {
            position: fixed;
            top: -128px;
            left: 0;
            right: 0;
            height: 118px;
        }

        .company-details { font-size: 7.5pt; color: #5C6169; text-align: right; }
        .company-tag { font-size: 8pt; color: #141414; text-align: right; }

        /* Filet sous l'en-tête : segment rouge puis trait gris, comme la facture. */
        .rule-accent { height: 2.2pt; background-color: #EE1B0F; width: 78px; }
        .rule-rest { height: 0.6pt; background-color: #E4E5E8; }

        .doc-title { font-size: 20pt; font-weight: bold; letter-spacing: -0.5px; }
        .doc-title .a { color: #141414; }
        .doc-title .b { color: #EE1B0F; }

        .doc-meta { font-size: 8pt; color: #141414; text-align: right; }
        .doc-meta .k { color: #5C6169; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ---- Pied de page fixe ---- */
        .doc-footer {
            position: fixed;
            bottom: -46px;
            left: 0;
            right: 0;
            height: 38px;
            font-size: 7.5pt;
            color: #5C6169;
            border-top: 0.6pt solid #E4E5E8;
            padding-top: 6px;
            text-align: center;
        }

        .doc-footer .sep { color: #EE1B0F; }
        /* Numéro seul : DomPDF renvoie 0 pour counter(pages) dans un bloc
           fixe, ce qui affichait « Page 1 / 0 ». Mieux vaut pas de total
           qu'un total faux. */
        .doc-footer .pagenum:after { content: "Page " counter(page); }

        /* ---- Blocs adresses ---- */
        .address-table { width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 8px 0; }
        .address-box {
            width: 50%;
            background-color: #F5F6F7;
            border-left: 3px solid #EE1B0F;
            padding: 8px 10px;
            vertical-align: top;
        }
        .address-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #EE1B0F;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .address-box .name { font-weight: bold; color: #141414; font-size: 10pt; }
        .address-box .detail { font-size: 8pt; color: #5C6169; }

        /* ---- Tableau des lignes ---- */
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; }

        /* Bandeau sombre : c'est le repère visuel du modèle. */
        table.lines thead th {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #FFFFFF;
            background-color: #141414;
            padding: 7px 8px;
            text-align: left;
        }

        table.lines tbody td {
            padding: 6px 8px;
            border-bottom: 0.5pt solid #E4E5E8;
            font-size: 9pt;
        }

        table.lines .num { text-align: right; }
        table.lines .center { text-align: center; }

        table.lines tfoot td {
            padding: 8px;
            font-size: 10pt;
            font-weight: bold;
            color: #FFFFFF;
            background-color: #141414;
            border-left: 3px solid #EE1B0F;
        }

        /* ---- Notes ---- */
        .notes-box { margin-top: 14px; border: 0.5pt solid #E4E5E8; padding: 8px 10px; min-height: 44px; }
        .notes-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #EE1B0F;
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* ---- Signatures ---- */
        .signature-table { width: 100%; margin-top: 26px; border-collapse: separate; border-spacing: 8px 0; }
        .signature-box { width: 50%; border: 0.5pt solid #E4E5E8; height: 80px; padding: 6px 10px; vertical-align: top; }
        .signature-box .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5C6169;
            font-weight: bold;
        }

        .muted { color: #5C6169; }
    </style>
</head>
<body>
    <div class="doc-header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 40%;">
                    {{-- Le logo est une image : DomPDF ne rend pas le SVG de
                         façon fiable, et un tracé CSS ne reproduit pas la
                         forme exacte de la marque. --}}
                    <img src="{{ public_path('images/igoutech-logo.png') }}" alt="iGouTech" style="height: 40px;">
                </td>
                <td style="vertical-align: top; width: 60%;">
                    <div class="company-tag">Solutions informatiques &amp; services numériques</div>
                    <div class="company-details">
                        Inzegane — Agadir, Maroc<br>
                        0661 24 17 83 &nbsp;|&nbsp; 0528 83 88 46<br>
                        contact&#64;igoutech.ma
                    </div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 8px;">
            <div class="rule-accent"></div>
            <div class="rule-rest"></div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="vertical-align: bottom; width: 55%;">
                    <div class="doc-title">@yield('document_title')</div>
                </td>
                <td style="vertical-align: bottom; width: 45%;">
                    <div class="doc-meta">@yield('document_meta')</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left; width: 20%;">&nbsp;</td>
                <td style="text-align: center; width: 60%;">
                    Inzegane — Agadir <span class="sep">&#9670;</span> 0661 24 17 83
                    <span class="sep">&#9670;</span> 0528 83 88 46
                </td>
                <td style="text-align: right; width: 20%;"><span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <main>
        @yield('content')
    </main>
</body>
</html>
