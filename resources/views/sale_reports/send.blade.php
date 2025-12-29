@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.sale_report.send_title') }} #{{ $saleReport->id }} - {{ $supplier->name }}</h1>

    <form action="{{ route('sale-reports.doSend', [$supplier, $saleReport]) }}" method="POST">
        @csrf

        {{-- Destinataires --}}
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('messages.sale_report.available_recipients') }}</label>
            <div id="recipientWarning" class="alert alert-warning">{{ __('messages.sale_report.no_recipient_selected') }}</div>
            <div id="selectedRecipients"></div>

            <div class="dropdown mt-2">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="contactsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ __('messages.sale_report.add_from_list') }}
                </button>
                <ul class="dropdown-menu p-3" aria-labelledby="contactsDropdown">
                    @foreach($contacts as $contact)
                        <div class="form-check">
                            <input class="form-check-input recipient-checkbox" type="checkbox" name="recipients[]" value="{{ $contact->email }}" id="contact-{{ $contact->id }}">
                            <label class="form-check-label" for="contact-{{ $contact->id }}">
                                {{ $contact->name ?? __('messages.sale_report.contact') }} - {{ $contact->email }}
                            </label>
                        </div>
                    @endforeach
                </ul>
            </div>

            {{-- Ajouter manuellement --}}
            <div class="input-group mt-2">
                <input type="email" id="newRecipient" class="form-control" placeholder="{{ __('messages.sale_report.new_email_placeholder') }}">
                <button type="button" id="addRecipientBtn" class="btn btn-primary">{{ __('messages.btn.add') }}</button>
            </div>
        </div>

        {{-- Titre --}}
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('messages.sale_report.subject') }}</label>
            <input type="text" name="subject" class="form-control" value="{{ __('messages.sale_report.subject_value', ['id' => $saleReport->id]) }}">
        </div>

        {{-- Corps du message --}}
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('messages.sale_report.message') }}</label>
            <textarea id="body" name="body" class="form-control">
                {!! view('emails.sale_report', ['saleReport' => $saleReport])->render() !!}
            </textarea>
        </div>

        {{-- Boutons --}}
        <div class="mt-3">
            <button type="submit" class="btn btn-success" id="sendButton" disabled>
                <i class="bi bi-envelope-fill"></i> {{ __('messages.sale_report.send_report') }}
            </button>
            <a href="{{ route('sale-reports.show', [$supplier, $saleReport]) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/{{ config('app.tiny_mce') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#body',
    height: 400,
    menubar: false,
    plugins: 'lists link image table code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link image | code',
});

document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('addRecipientBtn');
    const newRecipientInput = document.getElementById('newRecipient');
    const selectedContainer = document.getElementById('selectedRecipients');
    const checkboxes = document.querySelectorAll('.recipient-checkbox');
    const warning = document.getElementById('recipientWarning');
    const sendButton = document.getElementById('sendButton');

    const manualRecipientsContainer = document.createElement('div');
    selectedContainer.appendChild(manualRecipientsContainer);

    function updateRecipientState() {
        selectedContainer.querySelectorAll('.auto-selected').forEach(el => el.remove());

        const selected = [...document.querySelectorAll('.recipient-checkbox:checked')];
        if(selected.length > 0 || manualRecipientsContainer.children.length > 0) {
            warning.style.display = 'none';
            sendButton.disabled = false;
        } else {
            warning.style.display = 'block';
            sendButton.disabled = true;
        }

        selected.forEach(cb => {
            const wrapper = document.createElement('div');
            wrapper.classList.add('form-check', 'd-inline-block', 'me-2', 'auto-selected');

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.classList.add('form-check-input');
            checkbox.checked = true;

            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.textContent = cb.value;

            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            selectedContainer.insertBefore(wrapper, manualRecipientsContainer);

            checkbox.addEventListener('change', function() {
                cb.checked = false;
                wrapper.remove();
                updateRecipientState();
            });
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateRecipientState));

    addBtn.addEventListener('click', function() {
        const email = newRecipientInput.value.trim();
        if(email === '') return;

        const wrapper = document.createElement('div');
        wrapper.classList.add('form-check', 'd-inline-block', 'me-2');

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'recipients[]';
        hiddenInput.value = email;

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.classList.add('form-check-input');
        checkbox.checked = true;

        const label = document.createElement('label');
        label.classList.add('form-check-label');
        label.textContent = email;

        wrapper.appendChild(hiddenInput);
        wrapper.appendChild(checkbox);
        wrapper.appendChild(label);
        manualRecipientsContainer.appendChild(wrapper);

        checkbox.addEventListener('change', function() {
            wrapper.remove();
            updateRecipientState();
        });

        newRecipientInput.value = '';
        updateRecipientState();
    });

    updateRecipientState();
});
</script>
@endpush
