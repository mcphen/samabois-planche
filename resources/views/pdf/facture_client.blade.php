<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture Client</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        h1, h2, h3 { margin: 0 0 10px; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #000; }
        .invoice-info { display: flex; justify-content: space-between; margin: 20px 0; }
        .invoice-info div { width: 48%; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .invoice-table th { background-color: #f4f4f4; }
        .total { text-align: right; margin-top: 20px; }
        .total h3 { background: #f4f4f4; padding: 10px; border-radius: 5px; display: inline-block; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
<div class="container">
    <!-- En-tête -->
    <div class="header">
        <h1>Compte Client</h1>
        <!--p>{{ now()->format('d/m/Y') }}</p-->
    </div>

    <!-- Informations Facture -->
    <div class="invoice-info">

        <div>
            <h3>Client</h3>
            <p><strong>Nom :</strong> {{ $client->name }}</p>
            <p><strong>Adresse :</strong> {{ $client->address ?? 'Non spécifiée' }}</p>
        </div>
    </div>

    <!-- Tableau des Articles -->
    <table class="invoice-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Numéro de Facture</th>
            <th>Montant Facture</th>
            <th>Paiement</th>
            <th>Montant Restant</th>

        </tr>
        </thead>
        <tbody>
        @foreach ($data as $key => $dt)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($dt['date'])->format('d/m/y') }}</td>
                <td>
                    @if ($dt['facture'])
                        {{ $dt['facture']->matricule }}
                    @elseif ($dt['isSolde'])
                        <em>Ajustement</em>
                    @endif
                </td>
                <td>
                    @if ($dt['invoice']>0)
                    {{ number_format( (int) $dt['invoice'], 0, ',', '.') }} F
                    @endif
                </td>
                <td>
                    @if ($dt['payment']>0)
                    {{ number_format( (int) $dt['payment'], 0, ',', '.') }} F
                    @endif
                </td>
                <td>
                    @if ($key === count($data) - 1)
                        {{ number_format( (int) $client->amount_solde, 0, ',', '.') }} F
                    @else
                        {{ number_format( (int) $dt['cumul'], 0, ',', '.') }} F
                    @endif
                </td>
            </tr>
        @endforeach

        </tbody>
    </table>




</div>
</body>
</html>
