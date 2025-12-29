@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.expense_categories.add') }}</h1>

    <form action="{{ route('stores.expense-categories.store', $site) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ __('messages.common.name') }}</label>
            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.common.description') }}</label>
            <textarea class="form-control" name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.Ajouter') }}</button>
        <a href="{{ route('stores.expense-categories.index', $site) }}" class="btn btn-secondary">{{ __('messages.Annuler') }}</a>
    </form>
</div>
@endsection
