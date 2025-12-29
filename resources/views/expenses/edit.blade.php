@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.expenses.edit') }} - {{ $site->name }}</h1>

    <form action="{{ route('stores.expenses.update', [$site, $expense]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">{{ __('messages.expenses.category') }}</label>
            <select name="category_id" class="form-select" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $expense->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.expenses.name') }}</label>
            <input type="text" class="form-control" name="name" value="{{ old('name', $expense->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.expenses.description') }}</label>
            <textarea class="form-control" name="description">{{ old('description', $expense->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.expenses.amount') }}</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount', $expense->amount) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.expenses.document_file') }}</label>
            <input type="file" class="form-control" name="document">
            @if($expense->document)
                <small class="text-muted">{{ __('messages.expenses.has_document') }}: <a href="{{ Storage::url($expense->document) }}" target="_blank">{{ __('messages.expenses.view') }}</a></small>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('stores.expenses.index', $site) }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
