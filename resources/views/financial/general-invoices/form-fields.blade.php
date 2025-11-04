<div class="mb-3">
    <label>@t('Libellé')</label>
    <input type="text" name="label" class="form-control" value="{{ old('label', $invoice->label ?? '') }}" required>
</div>
<div class="mb-3">
    <label>@t('Note')</label>
    <textarea name="note" class="form-control">{{ old('note', $invoice->note ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label>@t('Montant')</label>
    <input type="number" name="amount" step="0.01" class="form-control" value="{{ old('amount', $invoice->amount ?? '') }}" required>
</div>
<div class="mb-3">
    <label>@t('Date limite')</label>
    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', isset($invoice) && $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
</div>
<div class="mb-3">
    <label>@t('Statut')</label>
    <select name="status" class="form-select" required>
        <option value="pending" @selected(old('status', $invoice->status ?? 'pending')=='pending')>@t('À payer')</option>
        <option value="paid" @selected(old('status', $invoice->status ?? '')=='paid')>@t('Payée')</option>
    </select>
</div>
<div class="mb-3">
    <label>@t('Compte')</label>
    <select name="account_id" class="form-select" required>
        <option value="">-- @t('Choisir un compte') --</option>
        @foreach($accounts as $account)
        <option value="{{ $account->id }}" @selected(old('account_id', $invoice->account_id ?? '')==$account->id)>{{ $account->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>@t('Catégorie')</label>
    <select name="category_id" class="form-select">
        <option value="">-- @t('Aucune catégorie') --</option>
        @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected(old('category_id', $invoice->category_id ?? '')==$category->id)>
            {{ $category->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>@t('Pièce jointe')</label>
    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @if(!isset($invoice)) required @endif>
    @if(isset($invoice) && $invoice->attachment)
        <small class="form-text text-muted">
            <a href="{{ route('financial.general-invoices.attachment', [$store->id, $invoice->id]) }}" target="_blank">@t('Voir le fichier actuel')</a>
        </small>
    @endif
</div>
