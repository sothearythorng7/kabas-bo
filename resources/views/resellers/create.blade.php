@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('resellers.title_create')</h1>

    <form action="{{ route('resellers.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">@t('resellers.name')</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">@t('resellers.type')</label>
            <select name="type" class="form-select" required>
                <option value="buyer">@t('resellers.type_buyer')</option>
                <option value="consignment">@t('resellers.type_consignment')</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> @t('resellers.save')
        </button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> @t('resellers.cancel')
        </a>
    </form>
</div>
@endsection
