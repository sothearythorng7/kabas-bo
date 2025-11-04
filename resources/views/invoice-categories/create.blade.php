@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('Créer une catégorie de facture')</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('invoice-categories.store', request()->only('store_id')) }}" method="POST">
                @csrf
                @if(request('store_id'))
                    <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                @endif
                @include('invoice-categories.form-fields')

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> @t('Créer')
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
