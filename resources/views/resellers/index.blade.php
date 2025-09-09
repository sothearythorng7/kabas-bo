@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Resellers</h1>

    <a href="{{ route('resellers.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Add Reseller
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Contacts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($resellers as $reseller)
            <tr>
                <td>{{ $reseller->name }}</td>
                <td>{{ ucfirst($reseller->type) }}</td>
                <td>
                    @foreach($reseller->contacts as $c)
                        <div>{{ $c->name }} ({{ $c->email }})</div>
                    @endforeach
                </td>
                <td>
                    <a href="{{ route('resellers.show', $reseller) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('resellers.edit', $reseller) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('resellers.destroy', $reseller) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this reseller?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No resellers found.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $resellers->links() }}
</div>
@endsection
