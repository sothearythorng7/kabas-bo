@extends('layouts.app')

@section('content')
@php $locales = config('app.website_locales'); @endphp

<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.title_edit') }} - {{ $product->ean }}<br /><small>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</small></h1>
    <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>

    {{-- Onglets desktop --}}
    <ul class="nav nav-tabs d-none d-md-flex" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">{{ __('messages.product.tab_general') }}</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
                {{ __('messages.categories') }}
                <span class="badge bg-{{ ($product->categories->count() ?? 0) > 0 ? 'success' : 'danger' }}">{{ $product->categories->count() ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-suppliers" type="button" role="tab">
                {{ __('messages.menu.suppliers') }}
                <span class="badge bg-{{ ($product->suppliers->count() ?? 0) > 0 ? 'success' : 'danger' }}">{{ $product->suppliers->count() ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-stores" type="button" role="tab">{{ __('messages.product.tab_stores') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos" type="button" role="tab">{{ __('messages.product.tab_photos') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-descriptions" type="button" role="tab">{{ __('messages.product.tab_descriptions') }}</button></li>
    </ul>

    {{-- Accordéon mobile --}}
    <div class="accordion d-md-none" id="productAccordion">
        @php
            $sections = ['general'=>__('messages.product.tab_general'),'categories'=>__('messages.categories'),'suppliers'=>__('messages.menu.suppliers'),'stores'=>__('messages.product.tab_stores'),'photos'=>__('messages.product.tab_photos'),'descriptions'=>__('messages.product.tab_descriptions')];
            $first = true;
        @endphp
        @foreach($sections as $key=>$label)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $key }}">
                    <button class="accordion-button @if(!$first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $key }}" aria-expanded="@if($first)true @else false @endif" aria-controls="collapse-{{ $key }}">
                        {{ $label }}
                    </button>
                </h2>
                <div id="collapse-{{ $key }}" class="accordion-collapse collapse @if($first) show @endif" aria-labelledby="heading-{{ $key }}" data-bs-parent="#productAccordion">
                    <div class="accordion-body">
                        {{-- Contenu du tab dupliqué pour mobile --}}
                        @include('products.partials.tab_'.$key)
                    </div>
                </div>
            </div>
            @php $first=false; @endphp
        @endforeach
    </div>

    {{-- Contenu onglets desktop --}}
    <div class="tab-content mt-3 d-none d-md-block">
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            @include('products.partials.tab_general')
        </div>
        <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            @include('products.partials.tab_categories')
        </div>
        <div class="tab-pane fade" id="tab-suppliers" role="tabpanel">
            @include('products.partials.tab_suppliers')
        </div>
        <div class="tab-pane fade" id="tab-stores" role="tabpanel">
            @include('products.partials.tab_stores')
        </div>
        <div class="tab-pane fade" id="tab-photos" role="tabpanel">
            @include('products.partials.tab_photos')
        </div>
        <div class="tab-pane fade" id="tab-descriptions" role="tabpanel">
            @include('products.partials.tab_descriptions')
        </div>
    </div>

    {{-- Toutes les modales à la fin --}}
    <div>
        {{-- Modal Category --}}
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">...</div></div>
        </div>
        {{-- Modal Supplier --}}
        <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">...</div></div>
        </div>
        {{-- Modal Store --}}
        <div class="modal fade" id="addStoreModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">...</div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function(){
    // Onglet actif
    let hash = window.location.hash;
    if(hash){
        let triggerEl = document.querySelector(`button[data-bs-target="${hash}"]`);
        if(triggerEl) new bootstrap.Tab(triggerEl).show();
    }

    // Sauvegarde onglet actif
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn=>{
        btn.addEventListener('shown.bs.tab', e=>{
            history.replaceState(null,null,e.target.dataset.bsTarget);
        });
    });

    // Force affichage modales
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn=>{
        btn.addEventListener('click', e=>{
            e.preventDefault();
            let target = btn.getAttribute('data-bs-target');
            let modalEl = document.querySelector(target);
            if(modalEl) new bootstrap.Modal(modalEl).show();
        });
    });
});
</script>
@endpush
