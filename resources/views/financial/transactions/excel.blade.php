<table>
    <thead>
        <tr>
            <th>@t("date")</th>
            <th>@t("Libellé")</th>
            <th>@t("Compte")</th>
            <th>@t("Montant")</th>
            <th>@t("Méthode")</th>
            <th>@t("Solde après")</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $t)
            <tr>
                <td>{{ $t->transaction_date->format('d/m/Y') }}</td>
                <td>{{ $t->label }}</td>
                <td>{{ $t->account->code }} - {{ $t->account->name }}</td>
                <td>{{ $t->direction === 'debit' ? '-' : '+' }} {{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                <td>{{ $t->paymentMethod->name }}</td>
                <td>{{ number_format($t->balance_after, 2) }} {{ $t->currency }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
