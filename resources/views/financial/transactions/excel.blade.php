<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Libellé</th>
            <th>Compte</th>
            <th>Montant</th>
            <th>Méthode</th>
            <th>Solde après</th>
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
