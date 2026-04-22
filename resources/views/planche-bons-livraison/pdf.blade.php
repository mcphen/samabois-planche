<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $bon->numero_bl }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 0; }
        h1, h2, h3 { margin: 0 0 5px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .invoice-table th { background-color: #f4f4f4; }
        .invoice-table tfoot td, .invoice-table tfoot th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .total { text-align: right; margin-top: 20px; }
        .total h3 { background: #f4f4f4; padding: 10px; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">

    <table width="100%" style="margin: 5px 0;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <h3>Facture N° {{ $bon->numero_bl }}</h3>
                <p><strong>Date de livraison :</strong> {{ $bon->date_livraison ? \Carbon\Carbon::parse($bon->date_livraison)->format('d/m/Y') : '-' }}</p>
            </td>
            <td style="width: 48%; vertical-align: top; text-align: right;">
                <h3>Client</h3>
                <p><strong>Nom :</strong> {{ $bon->client?->name ?? '-' }}</p>
                @if ($bon->client?->address)
                    <p><strong>Adresse :</strong> {{ $bon->client->address }}</p>
                @endif
            </td>
        </tr>
    </table>

    @php
        $totalQteLivree  = $bon->lignes->sum('quantite_livree');
        $totalMontant    = $bon->lignes->sum('prix_total');
    @endphp

    <table class="invoice-table">
        <thead>
            <tr>
                <th>Code couleur</th>
                <th>Epaisseur</th>
                <th>Qte livree</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bon->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->plancheDetail?->couleur?->code ?? '-' }}</td>
                    <td>{{ number_format((float) ($ligne->plancheDetail?->epaisseur ?? 0), 2, '.', '') }}</td>
                    <td>{{ (int) $ligne->quantite_livree }}</td>
                    <td>{{ number_format((int) $ligne->prix_unitaire, 0, ',', '.') }} CFA</td>
                    <td>{{ number_format((int) $ligne->prix_total, 0, ',', '.') }} CFA</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right">Totaux</td>
                <td>{{ (int) $totalQteLivree }}</td>
                <td></td>
                <td>{{ number_format((int) $totalMontant, 0, ',', '.') }} CFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="total">
        <h3>Total : {{ number_format((int) $totalMontant, 0, ',', '.') }} CFA</h3>
    </div>

</div>
</body>
</html>
