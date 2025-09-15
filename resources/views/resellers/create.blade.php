@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>@t("Add Reseller")</h1>

    <form action="{{ route('resellers.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">@t("name")</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">@t("type")</label>
            <select name="type" class="form-select" required>
                <option value="buyer">@t("Buyer (one-time invoice)")</option>
                <option value="consignment">@t("Consignment (monthly reports)")</option>
            </select>
        </div>
        <button class="btn btn-success">@t("save")</button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">@t("cancel")</a>
    </form>
</div>
@endsection
