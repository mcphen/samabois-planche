<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->matricule }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 0; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .invoice-table th { background-color: #f4f4f4; }
        .total { text-align: right; margin-top: 20px; }
        .total h3 { background: #f4f4f4; padding: 10px; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <table width="100%" style="margin: 5px 0;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <h3>Facture N {{ $invoice->matricule }}</h3>
                <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d/m/Y') }}</p>
                <p><strong>BL source :</strong> {{ $invoice->plancheBonLivraison?->numero_bl ?? '-' }}</p>
            </td>
            <td style="width: 48%; vertical-align: top; text-align: right;">
                <h3>Client</h3>
                <p><strong>Nom :</strong> {{ $invoice->client->name }}</p>
                <p><strong>Adresse :</strong> {{ $invoice->client->address ?? 'Non specifiee' }}</p>
            </td>
        </tr>
    </table>

    <table class="invoice-table">
        <thead>
        <tr>
            <th>Fournisseur</th>
            <th>Contrat</th>
            <th>Code couleur</th>
            <th>Epaisseur</th>
            <th>Qté prévue</th>
            <th>Qté livrée</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($invoice->plancheBonLivraison?->lignes ?? [] as $ligne)
            <tr>
                <td>{{ $ligne->plancheDetail?->planche?->contrat?->supplier?->name ?? '-' }}</td>
                <td>{{ $ligne->plancheDetail?->planche?->contrat?->numero ?? '-' }}</td>
                <td>{{ $ligne->plancheDetail?->couleur?->code ?? '-' }}</td>
                <td>{{ number_format((float) ($ligne->plancheDetail?->epaisseur ?? 0), 2, ',', '.') }}</td>
                <td>{{ (int) ($ligne->plancheDetail?->quantite_prevue ?? 0) }}</td>
                <td>{{ (int) $ligne->quantite_livree }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">
        <h3>Total facture : {{ number_format((int) $invoice->total_price, 0, ',', '.') }} F</h3>
    </div>
</div>
</body>
</html>
