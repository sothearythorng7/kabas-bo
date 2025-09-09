@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Edit Reseller</h1>

    <a href="{{ route('resellers.deliveries.create', $reseller) }}" class="btn btn-success">
        Nouvelle commande
    </a>

    <form action="{{ route('resellers.update', $reseller) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ $reseller->name }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="buyer" @selected($reseller->type=='buyer')>Buyer</option>
                <option value="consignment" @selected($reseller->type=='consignment')>Consignment</option>
            </select>
        </div>
        <button class="btn btn-success">Update</button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
