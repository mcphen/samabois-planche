<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chiffre d'Affaires & Bénéfice</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h2>Chiffre d'Affaires & Bénéfice par Mois</h2>
<p>Date d'export: {{ now()->format('d/m/Y H:i') }}</p>

<table>
    <thead>
    <tr>
        <th>Mois</th>
        <th>Année</th>
        <th>Chiffre d'Affaires (FCFA)</th>
        <th>Coût de Base (FCFA)</th>
        <th>Bénéfice (FCFA)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($stats as $stat)
        <tr>
            <td>{{ $stat->month }}</td>
            <td>{{ $stat->year }}</td>
            <td>{{ number_format($stat->total_revenue, 0, ',', '.') }} F</td>
            <td>{{ number_format($stat->cost_base, 0, ',', '.') }} F</td>
            <td style="color: {{ $stat->profit >= 0 ? 'green' : 'red' }}">
                {{ number_format($stat->profit, 0, ',', '.') }} F
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
