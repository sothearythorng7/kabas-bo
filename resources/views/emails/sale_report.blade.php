<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de ventes #{{ $saleReport->id }}</title>
</head>
<body>
    <p>Bonjour,</p>

    <p>Veuillez trouver ci-joint le rapport de ventes <strong>#{{ $saleReport->id }}</strong> 
       pour la période <strong>{{ $saleReport->period_start->format('d/m/Y') }}</strong> 
       - <strong>{{ $saleReport->period_end->format('d/m/Y') }}</strong>.</p>

    <p>Magasin concerné : <strong>{{ $saleReport->store->name }}</strong></p>
    <p>Montant théorique total : <strong>{{ number_format($saleReport->total_amount_theoretical, 2) }} $</strong></p>

    <p>Bien cordialement,</p>
    <p>L’équipe {{ config('app.name') }}</p>
</body>
</html>
