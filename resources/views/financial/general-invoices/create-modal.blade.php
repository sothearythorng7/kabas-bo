<div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('financial.general-invoices.store', $store->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">@t('Ajouter une facture')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>@t('Libellé')</label>
                    <input type="text" name="label" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>@t('Note')</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label>@t('Montant')</label>
                    <input type="number" name="amount" step="0.01" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>@t('Date limite')</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label>@t('Statut')</label>
                    <select name="status" class="form-select" required>
                        <option value="pending">@t('À payer')</option>
                        <option value="paid">@t('Payée')</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>@t('Compte')</label>
                    <select name="account_id" class="form-select" required>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>@t('Pièce jointe')</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@t('Annuler')</button>
                <button type="submit" class="btn btn-primary">@t('Enregistrer')</button>
            </div>
        </form>
    </div>
</div>
