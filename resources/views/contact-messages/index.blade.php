@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Messages de contact</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="badge bg-warning">{{ $unreadCount }} non lus</span>
        </div>
        <form method="GET" action="{{ route('contact-messages.index') }}">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">-- Tous les messages --</option>
                <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Non lus</option>
                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Lus</option>
            </select>
        </form>
    </div>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>Date</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Sujet</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($messages as $message)
                <tr class="{{ !$message->is_read ? 'table-warning' : '' }}">
                    <td style="width: 1%; white-space: nowrap;">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMessage{{ $message->id }}" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('contact-messages.show', $message) }}">
                                        <i class="bi bi-eye-fill"></i> Voir
                                    </a>
                                </li>
                                @if(!$message->is_read)
                                <li>
                                    <form action="{{ route('contact-messages.mark-as-read', $message) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item" type="submit">
                                            <i class="bi bi-check"></i> Marquer comme lu
                                        </button>
                                    </form>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('contact-messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Supprimer ce message ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash-fill"></i> Supprimer
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                    <td>{{ $message->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong>{{ $message->name }}</strong>
                        @if(!$message->is_read)
                            <i class="bi bi-circle-fill text-warning" style="font-size: 0.5rem;"></i>
                        @endif
                    </td>
                    <td>{{ $message->email }}</td>
                    <td>{{ $message->subject ?? '-' }}</td>
                    <td class="text-center">
                        @if($message->is_read)
                            <span class="badge bg-success">Lu</span>
                        @else
                            <span class="badge bg-warning">Non lu</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $messages->links() }}
</div>
@endsection
