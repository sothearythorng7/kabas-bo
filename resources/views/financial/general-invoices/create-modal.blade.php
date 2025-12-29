<div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('financial.general-invoices.store', $store->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.general_invoices.add_invoice') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.label') }}</label>
                    <input type="text" name="label" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.note') }}</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.amount') }}</label>
                    <input type="number" name="amount" step="0.01" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.due_date') }}</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.blog_post.status') }}</label>
                    <select name="status" class="form-select" required>
                        <option value="pending">{{ __('messages.general_invoices.status_pending') }}</option>
                        <option value="paid">{{ __('messages.general_invoices.status_paid') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.account') }}</label>
                    <select name="account_id" class="form-select" required>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>{{ __('messages.general_invoices.attachment') }}</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
            </div>
        </form>
    </div>
</div>
