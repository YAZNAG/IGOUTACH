<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page {
            margin: 150px 40px 70px 40px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Helvetica, sans-serif;
            font-size: 9pt;
            color: #1F2937;
            line-height: 1.45;
        }

        /* ---- En-tête fixe ---- */
        .doc-header {
            position: fixed;
            top: -120px;
            left: 0;
            right: 0;
            height: 110px;
        }

        .doc-header .band {
            width: 100%;
            border-bottom: 2pt solid #0EA5E9; /* filet accent sous le titre */
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0B2A5B;
        }

        .company-details {
            font-size: 7.5pt;
            color: #647A99;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0B2A5B;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
        }

        .doc-meta {
            font-size: 8pt;
            color: #1F2937;
            text-align: right;
        }

        .doc-meta strong { color: #0B2A5B; }

        /* ---- Pied de page fixe ---- */
        .doc-footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 40px;
            font-size: 7pt;
            color: #647A99;
            border-top: 0.3pt solid #E3EAF2;
            padding-top: 5px;
        }

        .doc-footer .pagenum:after {
            content: "Page " counter(page) " / " counter(pages);
        }

        /* ---- Blocs adresses ---- */
        .address-table { width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 8px 0; }
        .address-box {
            width: 50%;
            border: 0.3pt solid #E3EAF2;
            background-color: #FAFCFE;
            padding: 8px 10px;
            vertical-align: top;
        }
        .address-box .label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #647A99;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .address-box .name { font-weight: bold; color: #0B2A5B; }
        .address-box .detail { font-size: 8pt; color: #1F2937; }

        /* ---- Tableau des lignes ---- */
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.lines thead th {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #647A99;
            border-bottom: 0.6pt solid #E3EAF2;
            border-top: 0.3pt solid #E3EAF2;
            padding: 5px 6px;
            text-align: left;
        }

        table.lines tbody td {
            padding: 4px 6px;
            border-bottom: 0.3pt solid #E3EAF2;
            font-size: 9pt;
        }

        table.lines tbody tr:nth-child(even) td {
            background-color: #FAFCFE; /* lignes alternées */
        }

        table.lines .num { text-align: right; }
        table.lines .center { text-align: center; }

        table.lines tfoot td {
            padding: 6px;
            font-size: 9pt;
            font-weight: bold;
            color: #0B2A5B;
            border-top: 0.6pt solid #E3EAF2;
        }

        /* ---- Notes ---- */
        .notes-box {
            margin-top: 14px;
            border: 0.3pt solid #E3EAF2;
            padding: 8px 10px;
            min-height: 44px;
        }
        .notes-box .label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #647A99;
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* ---- Signatures ---- */
        .signature-table { width: 100%; margin-top: 26px; border-collapse: separate; border-spacing: 8px 0; }
        .signature-box {
            width: 50%;
            border: 0.3pt solid #E3EAF2;
            height: 80px;
            padding: 6px 10px;
            vertical-align: top;
        }
        .signature-box .label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #647A99;
            font-weight: bold;
        }

        .muted { color: #647A99; }
    </style>
</head>
<body>
    <div class="doc-header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 55%;">
                    <div class="company-name">IGOUTECH SARL</div>
                    <div class="company-details">
                        Zone industrielle Agadir — Maroc<br>
                        ICE : 000000000000000 · RC : 00000 · IF : 00000000<br>
                        Tél : +212 5 28 00 00 00 · contact&#64;igoutech.ma
                    </div>
                </td>
                <td style="vertical-align: top; width: 45%;">
                    <div class="doc-title">@yield('document_title')</div>
                    <div class="doc-meta">@yield('document_meta')</div>
                </td>
            </tr>
        </table>
        <div class="band" style="margin-top: 8px;"></div>
    </div>

    <div class="doc-footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left;">IGOUTECH SARL — Zone industrielle Agadir · ICE : 000000000000000</td>
                <td style="text-align: right;"><span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <main>
        @yield('content')
    </main>
</body>
</html>
