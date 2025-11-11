@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('contact_message.title')</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="badge bg-warning">{{ $unreadCount }} @t('contact_message.unread_count')</span>
        </div>
        <form method="GET" action="{{ route('contact-messages.index') }}">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">@t('contact_message.all_messages')</option>
                <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>@t('contact_message.unread')</option>
                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>@t('contact_message.read')</option>
            </select>
        </form>
    </div>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>@t('contact_message.date')</th>
                    <th>@t('contact_message.name')</th>
                    <th>@t('contact_message.email')</th>
                    <th>@t('contact_message.subject')</th>
                    <th class="text-center">@t('contact_message.status')</th>
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
                                        <i class="bi bi-eye-fill"></i> @t('contact_message.view')
                                    </a>
                                </li>
                                @if(!$message->is_read)
                                <li>
                                    <form action="{{ route('contact-messages.mark-as-read', $message) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item" type="submit">
                                            <i class="bi bi-check"></i> @t('contact_message.mark_as_read')
                                        </button>
                                    </form>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('contact-messages.destroy', $message) }}" method="POST" onsubmit="return confirm('@t('contact_message.delete_confirm')')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash-fill"></i> @t('contact_message.delete')
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
                            <span class="badge bg-success">@t('contact_message.status_read')</span>
                        @else
                            <span class="badge bg-warning">@t('contact_message.status_unread')</span>
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
