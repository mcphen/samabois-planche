<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique de la Caisse</title>
    <style>
        body { font-family: sans-serif; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .invoice-table th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h2>Historique de la Caisse</h2>
<p>Date d'export: {{ now()->format('d/m/Y H:i') }}</p>

<table class="invoice-table">
    <thead>
    <tr>
        <th style="width: 10% !important;">Date</th>
        <th style="width: 40% !important;">Objet</th>
        <th style="width: 10% !important;">Type</th>
        <th style="width: 20% !important;">Montant</th>

        <th style="width: 20% !important;">Solde</th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $transaction)
        <tr style="{{ $transaction['type'] == 'sortie' ? 'color: red;' : '' }}">
            <td>{{\Illuminate\Support\Carbon::parse($transaction['date'])->format('d/m/y') }}</td>
            <td>
                @if($transaction['type'] == 'sortie')
                    {{ $transaction['objet'] ?? 'N/A' }}
                @else
                    Paiement : {{ $transaction['client'] }}
                @endif
            </td>
            <td>{{ ucfirst($transaction['type']) }}</td>
            <td>{{ number_format( (int) $transaction['amount'], 0, ',', '.') }} </td>

            @if($transaction['type'] == 'sortie')
                <td style="color: red;">
                    {{ number_format( (int) $transaction['cumul'], 0, ',', '.')   ?? 0 }}
                </td>
            @else
                <td>
                    {{ number_format( (int) $transaction['cumul'], 0, ',', '.')   ?? 0 }}
                </td>
            @endif
        </tr>
    @endforeach

    </tbody>
</table>
</body>
</html>
