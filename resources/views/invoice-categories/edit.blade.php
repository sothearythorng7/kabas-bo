@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('Modifier la catégorie de facture')</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('invoice-categories.update', $invoiceCategory->id) }}" method="POST">
                @csrf
                @method('PUT')
                @if(request('store_id'))
                    <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                @endif
                @include('invoice-categories.form-fields', ['category' => $invoiceCategory])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> @t('Mettre à jour')
                    </button>
                    <a href="{{ route('invoice-categories.index', request()->only('store_id')) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> @t('Annuler')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
