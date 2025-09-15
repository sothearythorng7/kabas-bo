@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_edit') }}</h1>

    <form action="{{ route('resellers.update', $reseller) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">@t("name")</label>
            <input type="text" name="name" class="form-control" value="{{ $reseller->name }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="buyer" @selected($reseller->type=='buyer')>@t("Buyer")</option>
                <option value="consignment" @selected($reseller->type=='consignment')>@t("Consignment")</option>
            </select>
        </div>
        <button class="btn btn-success">@t("Update")</button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">@t("cancel")</a>
    </form>
</div>
@endsection
