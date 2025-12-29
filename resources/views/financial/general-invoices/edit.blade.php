@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.general_invoices.title_edit') }}</h1>
    @include('financial.layouts.nav')

    <form action="{{ route('financial.general-invoices.update', [$store->id, $generalInvoice->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('financial.general-invoices.form-fields', ['invoice' => $generalInvoice])
        <button type="submit" class="btn btn-primary">{{ __('messages.general_invoices.update') }}</button>
    </form>
</div>
@endsection
