<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->matricule }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 800px; margin: 0px auto; padding: 0px; /*border: 1px solid #ddd; border-radius: 8px;*/ }
        h1, h2, h3 { margin: 0 0 5px; }
        .header { text-align: center; padding-bottom: 1px; border-bottom: 2px solid #000; }
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
    <!-- En-tête
    <div class="header">
        <h1>FACTURE</h1>
        <p>{{ now()->format('d/m/Y') }}</p>
    </div>-->

    <!-- Informations Facture -->
    <table width="100%" style="margin: 5px 0;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <h3>Facture N° {{ $invoice->matricule }}</h3>
                <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d/m/Y') }}</p>
            </td>
            <td style="width: 48%; vertical-align: top; text-align: right;">
                <h3>Client</h3>
                <p><strong>Nom :</strong> {{ $invoice->client->name }}</p>
                <p><strong>Adresse :</strong> {{ $invoice->client->address ?? 'Non spécifiée' }}</p>
            </td>
        </tr>
    </table>


    <!-- Tableau des Articles -->
    <table class="invoice-table">
        <thead>
        <tr>
            <th style="width: 10%">Essence</th>
            <th style="width: 10%">Numéro Colis</th>
            <th style="width: 10%">Longueur</th>
            <th style="width: 10%">Épaisseur</th>
            <th style="width: 10%">Nb P.</th>
            <th style="width: 10%">Volume (m³)</th>
            <th style="width: 10%">Prix/m³ (F)</th>
            <th style="width: 20%">Prix Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->articleItem->article->essence }}</td>
                <td>{{ $item->articleItem->numero_colis }}</td>
                <td>{{ $item->articleItem->longueur }} </td>
                <td>{{ $item->articleItem->epaisseur }} </td>
                <td>{{ $item->articleItem->nombre_piece }} </td>
                <td>{{ $item->articleItem->volume }}  m³</td>
                <td>
                    @if ($item->price != 0)

                        {{ number_format( (int) $item->price, 0, ',', '.') }}

                    @endif
                </td>
                <td>
                    @if($item->total_price_item!=0)

                        {{ number_format( (int) $item->total_price_item, 0, ',', '.') }}

                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4" class="text-right font-weight-bold">Volume Total :</td>
            <td colspan="4" class="font-weight-bold">
                <strong>{{ number_format($invoice->items->sum(fn($item) => $item->articleItem->volume), 3, ',', '.') }} m³</strong>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-right font-weight-bold">Total colis :</td>
            <td colspan="4" class="font-weight-bold">
                <strong> {{count($invoice->items)}}</strong>
            </td>
        </tr>
        </tfoot>
    </table>

    <!-- Total -->
    <div class="total">
        <h3>Total  : {{ number_format( (int) $invoice->total_price, 0, ',', '.')   }} F</h3>
    </div>


</div>
</body>
</html>
