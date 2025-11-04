@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('Créer une facture')</h1>
    @include('financial.layouts.nav')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('financial.general-invoices.store', $store->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('financial.general-invoices.form-fields', ['invoice' => null])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> @t('Créer')
                    </button>
                    <a href="{{ route('financial.general-invoices.index', $store->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> @t('Annuler')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
