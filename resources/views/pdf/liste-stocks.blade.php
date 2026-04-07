<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des stocks</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 1000px; margin: 20px auto; padding: 20px; }
        h1 { margin: 0 0 10px; font-size: 20px; text-align: center; }
        .meta { text-align: center; color: #666; margin-bottom: 10px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <h1>Liste des stocks</h1>
    <div class="meta">Généré le {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
        <tr>
            <th>Essence</th>
            <th>Fournisseur</th>
            <th>N° Contrat</th>
            <th class="text-right">Bénéfice</th>
        </tr>
        </thead>
        <tbody>
        @foreach($articles as $article)
            <tr>
                <td>{{ $article->essence }}</td>
                <td>{{ optional($article->supplier)->name }}</td>
                <td>{{ $article->contract_number }}</td>
                <td class="text-right">{{ number_format((int)($article->profit ?? 0), 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
