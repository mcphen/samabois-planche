<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Clôture de caisse</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { margin-bottom: 15px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        .right { text-align: right; }
    </style>
    <meta http-equiv="Content-Security-Policy" content="script-src 'none'">
    <meta name="pdf-locked" content="true">
    <meta name="generator" content="DomPDF">
    <meta name="x-content-type-options" content="nosniff">
</head>
<body>
    <h1>Clôture de caisse</h1>
    <div class="meta">
        Caisse: <strong>{{ optional($closure->caisse)->name }}</strong><br>
        Période: <strong>{{ \Carbon\Carbon::parse($closure->start_date)->format('d/m/Y H:i') }}</strong>
        → <strong>{{ \Carbon\Carbon::parse($closure->end_date)->format('d/m/Y H:i') }}</strong><br>
        Statut: <strong>{{ strtoupper($closure->status) }}</strong>
    </div>

    <table>
        <tr>
            <th>Solde initial</th>
            <td class="right">{{ number_format($closure->initial_balance, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Total entrées</th>
            <td class="right">{{ number_format($closure->total_entries, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Transferts entrants</th>
            <td class="right">{{ number_format($closure->total_transfer_in, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Total sorties</th>
            <td class="right">{{ number_format($closure->total_exits, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Transferts sortants</th>
            <td class="right">{{ number_format($closure->total_transfer_out, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Solde théorique</th>
            <td class="right">{{ number_format($closure->theoretical_balance, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Solde réel</th>
            <td class="right">{{ number_format($closure->real_balance, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <th>Écart</th>
            <td class="right">{{ number_format($closure->difference, 2, ',', ' ') }}</td>
        </tr>
    </table>

    @if($closure->notes)
        <p><strong>Notes:</strong> {{ $closure->notes }}</p>
    @endif

    <p>
        Validé par: <strong>{{ optional($closure->validator)->name }}</strong><br>
        Créé par: <strong>{{ optional($closure->creator)->name }}</strong><br>
        Généré le: {{ now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
