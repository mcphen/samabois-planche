<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Livraison {{ $bon->numero_bl }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .doc-numero {
            font-size: 15px;
            color: #444;
        }
        .header-right {
            text-align: right;
            line-height: 1.8;
        }
        .client-block {
            margin-bottom: 16px;
            font-size: 14px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #bbb;
            padding: 7px 9px;
            text-align: left;
        }
        .invoice-table thead th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .invoice-table tfoot th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .footer-block {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 12px;
            color: #555;
            line-height: 1.8;
        }
    </style>
</head>
<body>
<div class="container">

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="doc-title">Bon de Livraison</div>
                <div class="doc-numero">{{ $bon->numero_bl }}</div>
            </td>
            <td style="width: 45%;" class="header-right">
                <div><strong>Date de livraison :</strong> {{ $bon->date_livraison ? \Carbon\Carbon::parse($bon->date_livraison)->format('d/m/Y') : '-' }}</div>
                @if ($bon->invoice)
                    <div><strong>Facture :</strong> {{ $bon->invoice->matricule }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="client-block">
        <strong>Client :</strong> {{ $bon->client?->name ?? '-' }}
    </div>

    @php
        $categorieLabels = ['mate' => 'Mate', 'semi_brillant' => 'Semi-brillant', 'brillant' => 'Brillant'];
        $totalQteLivree = $bon->lignes->sum('quantite_livree');
        $totalMontant   = $bon->lignes->sum('prix_total');
    @endphp

    <table class="invoice-table">
        <thead>
            <tr>
                <th>Fournisseur</th>
                <th>Contrat</th>
                <th>Code couleur</th>
                <th>Categorie</th>
                <th>Epaisseur</th>
                <th>Prevue</th>
                <th>Livree</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bon->lignes as $ligne)
                @php $cat = $ligne->plancheDetail?->categorie ?? ''; @endphp
                <tr>
                    <td>{{ $ligne->plancheDetail?->planche?->contrat?->supplier?->name ?? '-' }}</td>
                    <td>{{ $ligne->plancheDetail?->planche?->contrat?->numero ?? '-' }}</td>
                    <td>{{ $ligne->plancheDetail?->couleur?->code ?? '-' }}</td>
                    <td>{{ $categorieLabels[$cat] ?? ($cat ?: '-') }}</td>
                    <td>{{ number_format((float) ($ligne->plancheDetail?->epaisseur ?? 0), 2, '.', '') }}</td>
                    <td>{{ (int) ($ligne->plancheDetail?->quantite_prevue ?? 0) }}</td>
                    <td>{{ (int) $ligne->quantite_livree }}</td>
                    <td>{{ number_format((float) $ligne->prix_unitaire, 2, '.', '') }}</td>
                    <td>{{ number_format((float) $ligne->prix_total, 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">Totaux</th>
                <th>{{ (int) $totalQteLivree }}</th>
                <th></th>
                <th>{{ number_format((float) $totalMontant, 2, '.', '') }}</th>
            </tr>
        </tfoot>
    </table>

    @php
        $contrats     = $bon->lignes->map(fn($l) => $l->plancheDetail?->planche?->contrat?->numero)->filter()->unique()->values();
        $fournisseurs = $bon->lignes->map(fn($l) => $l->plancheDetail?->planche?->contrat?->supplier?->name)->filter()->unique()->values();
    @endphp

    @if ($contrats->isNotEmpty() || $fournisseurs->isNotEmpty())
        <div class="footer-block">
            @if ($contrats->isNotEmpty())
                <div><strong>Contrats :</strong> {{ $contrats->join(', ') }}</div>
            @endif
            @if ($fournisseurs->isNotEmpty())
                <div><strong>Fournisseurs :</strong> {{ $fournisseurs->join(', ') }}</div>
            @endif
        </div>
    @endif

</div>
</body>
</html>
