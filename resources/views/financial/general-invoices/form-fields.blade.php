<div class="mb-3">
    <label>{{ __('messages.general_invoices.label') }}</label>
    <input type="text" name="label" class="form-control" value="{{ old('label', $invoice->label ?? '') }}" required>
</div>
<div class="mb-3">
    <label>{{ __('messages.general_invoices.note') }}</label>
    <textarea name="note" class="form-control">{{ old('note', $invoice->note ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label>{{ __('messages.general_invoices.amount') }}</label>
    <input type="number" name="amount" step="0.00001" class="form-control" value="{{ old('amount', $invoice->amount ?? '') }}" required>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label>{{ __('messages.general_invoices.invoice_date') }}</label>
        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', isset($invoice) && $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>{{ __('messages.general_invoices.due_date') }}</label>
        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', isset($invoice) && $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="mb-3">
    <label>{{ __('messages.blog_post.status') }}</label>
    <select name="status" class="form-select" required>
        <option value="pending" @selected(old('status', $invoice->status ?? 'pending')=='pending')>{{ __('messages.general_invoices.status_pending') }}</option>
        @if(isset($invoice) && $invoice->status === 'paid')
            <option value="paid" selected>{{ __('messages.general_invoices.status_paid') }}</option>
        @endif
    </select>
</div>
<div class="mb-3">
    <label>{{ __('messages.general_invoices.account') }}</label>
    <select name="account_id" class="form-select" required>
        <option value="">-- {{ __('messages.general_invoices.choose_account') }} --</option>
        @foreach($accounts as $account)
        <option value="{{ $account->id }}" @selected(old('account_id', $invoice->account_id ?? '')==$account->id)>{{ $account->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>{{ __('messages.general_invoices.category') }}</label>
    <select name="category_id" class="form-select">
        <option value="">-- {{ __('messages.general_invoices.no_category') }} --</option>
        @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected(old('category_id', $invoice->category_id ?? '')==$category->id)>
            {{ $category->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label>{{ __('messages.general_invoices.attachment') }}</label>
    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
    @if(isset($invoice) && $invoice->attachment)
        <small class="form-text text-muted">
            <a href="{{ route('financial.general-invoices.attachment', [$store->id, $invoice->id]) }}" target="_blank">{{ __('messages.general_invoices.view_current_file') }}</a>
        </small>
    @endif
</div>
