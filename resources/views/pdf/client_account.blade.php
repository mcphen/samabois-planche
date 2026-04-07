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
        <h1>Compte Generale Client</h1>
        <!--p>{{ now()->format('d/m/Y') }}</p-->
    </div>


    @php
        $totalRemaining = 0;
    @endphp
    <!-- Tableau des Articles -->
    <table class="invoice-table">
        <thead>
        <tr>
            <th style="width: 30% !important;">Client</th>
            <th style="width: 30% !important;">Montant total facturé (FCFA)</th>
            <th style="width: 40% !important;">Montant Restant (FCFA)</th>


        </tr>
        </thead>
        <tbody>
        @foreach ($clients as $c)
            <tr>
                <td>{{ $c['name'] }}</td>
                <td>
                    @if($c['total_invoices']!=0)
                        {{ number_format( (int) $c['total_invoices'], 0, ',', '.') }} F

                    @endif
                </td>
                <td>
                    @if($c['remaining_due']!=0)
                    <span style="color :red">
                        {{ number_format( (int) $c['remaining_due'], 0, ',', '.') }} F
                    </span>
                    @endif

                </td>

            </tr>
            @php
                $totalRemaining += (int) $c['remaining_due'];
            @endphp
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2">Montant total due</td>

            <td>
            <span style="color :red">
                <strong>{{ number_format($totalRemaining, 0, ',', '.') }} F</strong>

            </span>
            </td>
        </tr>
        </tfoot>
    </table>




</div>
</body>
</html>
