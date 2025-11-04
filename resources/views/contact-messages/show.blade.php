@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">Message de {{ $contactMessage->name }}</h1>
        <a href="{{ route('contact-messages.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <div class="row">
                <div class="col-md-6">
                    <strong>De:</strong> {{ $contactMessage->name }}<br>
                    <strong>Email:</strong> <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Date:</strong> {{ $contactMessage->created_at->format('d/m/Y à H:i') }}<br>
                    <strong>Statut:</strong>
                    @if($contactMessage->is_read)
                        <span class="badge bg-success">Lu</span>
                    @else
                        <span class="badge bg-warning">Non lu</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($contactMessage->subject)
                <h5 class="mb-3">Sujet: {{ $contactMessage->subject }}</h5>
            @endif

            <div class="message-content p-3 bg-light rounded">
                {!! nl2br(e($contactMessage->message)) !!}
            </div>

            <div class="mt-4">
                <a href="mailto:{{ $contactMessage->email }}?subject=Re: {{ $contactMessage->subject }}" class="btn btn-primary">
                    <i class="bi bi-reply"></i> Répondre par email
                </a>

                @if(!$contactMessage->is_read)
                <form action="{{ route('contact-messages.mark-as-read', $contactMessage) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check"></i> Marquer comme lu
                    </button>
                </form>
                @endif

                <form action="{{ route('contact-messages.destroy', $contactMessage) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce message ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
