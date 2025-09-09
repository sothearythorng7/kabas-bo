@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Add Reseller</h1>

    <form action="{{ route('resellers.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="buyer">Buyer (one-time invoice)</option>
                <option value="consignment">Consignment (monthly reports)</option>
            </select>
        </div>
        <button class="btn btn-success">Save</button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
