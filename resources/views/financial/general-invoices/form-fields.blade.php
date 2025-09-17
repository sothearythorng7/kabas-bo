<div class="mb-3">
    <label>@t('Libellé')</label>
    <input type="text" name="label" class="form-control" value="{{ $invoice->label ?? '' }}" required>
</div>
<div class="mb-3">
    <label>@t('Note')</label>
    <textarea name="note" class="form-control">{{ $invoice->note ?? '' }}</textarea>
</div>
<div class="mb-3">
    <label>@t('Montant')</label>
    <input type="number" name="amount" step="0.01" class="form-control" value="{{ $invoice->amount ?? '' }}" required>
</div>
<div class="mb-3">
    <label>@t('Date limite')</label>
    <input type="date" name="due_date" class="form-control" value="{{ $invoice->due_date?->format('Y-m-d') ?? '' }}">
</div>
<div class="mb-3">
    <label>@t('Statut')</label>
    <select name="status" class="form-select" required>
        <option value="pending" @selected(($invoice->status ?? '')=='pending')>@t('À payer')</option>
        <option value="paid" @selected(($invoice->status ?? '')=='paid')>@t('Payée')</option>
    </select>
</div>
<div class="mb-3">
    <label>@t('Compte')</label>
    <select name="account_id" class="form-select" required>
        @foreach($accounts as $account)
        <option value="{{ $account->id }}" @selected(($invoice->account_id ?? '')==$account->id)>{{ $account->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>@t('Pièce jointe')</label>
    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @if(!isset($invoice)) required @endif>
</div>
