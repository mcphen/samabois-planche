<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste article</title>
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


    <!-- Informations Facture -->
    @if(!is_null($article))
        <div class="invoice-info">
            <div>
                <h3>Essence : {{ $article->essence }}</h3>
                <h3>Numero du contrat : {{ $article->contract_number }}</h3>
                <h3>Fournisseur : {{ $article->supplier->name }}</h3>
                <h3>Total des colis : {{ count($queries)}}</h3>
            </div>

        </div>
    @endif
    <!-- Tableau des Articles -->
    <table class="invoice-table">
        <thead>
        <tr>
            <th>Numéro Colis</th>
            <th>Longueur</th>
            <th>Épaisseur</th>
            <th>Nombre pieces</th>
            <th>Volume (m³)</th>

        </tr>
        </thead>
        <tbody>
        @foreach ($queries as $item)
            <tr>
                <td>{{ $item->numero_colis }}</td>
                <td>{{ $item->longueur }} </td>
                <td>{{ $item->epaisseur }} </td>
                <td>{{ $item->nombre_piece }} </td>
                <td>{{ number_format($item->volume, 3, ',', ' ') }}  m³</td>

            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4" class="text-right font-weight-bold">Volume Total :</td>
            <td class="font-weight-bold">
                {{ number_format($queries->sum('volume'), 3, ',', ' ') }} m³
            </td>
        </tr>
        </tfoot>
    </table>


</div>
</body>
</html>
