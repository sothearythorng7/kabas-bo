<table>
    <thead>
        <tr>
            <th>{{ __('messages.financial.date') }}</th>
            <th>{{ __('messages.financial.label') }}</th>
            <th>{{ __('messages.financial.account') }}</th>
            <th>{{ __('messages.financial.amount') }}</th>
            <th>{{ __('messages.financial.method') }}</th>
            <th>{{ __('messages.financial.balance_after') }}</th>
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
