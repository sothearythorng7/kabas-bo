@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.contact_message.message_from') }} {{ $contactMessage->name }}</h1>

    <div class="card">
        <div class="card-header bg-light">
            <div class="row">
                <div class="col-md-6">
                    <strong>{{ __('messages.contact_message.from') }}:</strong> {{ $contactMessage->name }}<br>
                    <strong>{{ __('messages.contact_message.email') }}:</strong> <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>{{ __('messages.contact_message.received_on') }}:</strong> {{ $contactMessage->created_at->format('d/m/Y à H:i') }}<br>
                    <strong>{{ __('messages.contact_message.status') }}:</strong>
                    @if($contactMessage->is_read)
                        <span class="badge bg-success">{{ __('messages.contact_message.status_read') }}</span>
                    @else
                        <span class="badge bg-warning">{{ __('messages.contact_message.status_unread') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($contactMessage->subject)
                <h5 class="mb-3">{{ __('messages.contact_message.subject') }}: {{ $contactMessage->subject }}</h5>
            @endif

            <div class="message-content p-3 bg-light rounded">
                {!! nl2br(e($contactMessage->message)) !!}
            </div>

            <div class="mt-4">
                <a href="mailto:{{ $contactMessage->email }}?subject=Re: {{ $contactMessage->subject }}" class="btn btn-primary">
                    <i class="bi bi-reply"></i> {{ __('messages.contact_message.reply_by_email') }}
                </a>

                @if(!$contactMessage->is_read)
                <form action="{{ route('contact-messages.mark-as-read', $contactMessage) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check"></i> {{ __('messages.contact_message.mark_as_read') }}
                    </button>
                </form>
                @endif

                <form action="{{ route('contact-messages.destroy', $contactMessage) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.contact_message.delete_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> {{ __('messages.contact_message.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <a href="{{ route('contact-messages.index') }}" class="btn btn-secondary mt-3">{{ __('messages.contact_message.back') }}</a>
</div>
@endsection
