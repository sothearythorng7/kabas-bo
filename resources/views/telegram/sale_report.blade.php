Bonjour,

Voici votre sale report pour la période {{ $report->period_start->format('d/m/Y') }} - {{ $report->period_end->format('d/m/Y') }}.

Détails du report :
- Magasin : {{ $report->store->name ?? '-' }}
- Montant théorique : ${{ number_format($report->total_amount_theoretical, 2) }}
- Montant facturé : ${{ $report->total_amount_invoiced ?? '-' }}
- Payé : {{ $report->is_paid ? 'Oui' : 'Non' }}
- Envoyé : {{ $report->sent_at ? 'Oui' : 'Non' }}

Merci,
{{ config('app.name') }}
