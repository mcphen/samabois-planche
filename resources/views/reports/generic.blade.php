<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Rapport' }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 10px 0; }
        .meta { font-size: 11px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f2f2f2; text-align: left; }
        tfoot td { font-weight: bold; }
    </style>
    @php
        $columns = $columns ?? [];
        $rows = $rows ?? [];
    @endphp
</head>
<body>
<h1>{{ $title ?? 'Rapport' }}</h1>
<div class="meta">
    Généré le {{ ($generated_at ?? now())->format('d/m/Y H:i') }}
 </div>

@if(empty($columns))
    <p>Aucune donnée.</p>
@else
    <table>
        <thead>
        <tr>
            @foreach($columns as $col)
                <th>{{ is_string($col) ? $col : (is_array($col) ? ($col['label'] ?? '') : '') }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($columns as $key => $col)
                    @php
                        $colKey = is_string($col) ? $col : (is_array($col) ? ($col['key'] ?? $key) : $key);
                    @endphp
                    <td>{{ data_get($row, $colKey) }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</body>
</html>
