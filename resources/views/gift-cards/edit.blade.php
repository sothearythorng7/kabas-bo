@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.gift_cards.edit') }}</h1>

    <a href="{{ route('gift-cards.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.gift_cards.back_to_list') }}
    </a>

    {{-- Onglets --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                {{ __('messages.gift_boxes.general') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
                {{ __('messages.product.categories') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('gift-cards.update', $giftCard) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('messages.gift_cards.general_info') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.form.amount') }} *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $giftCard->amount) }}" required>
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Noms par langue --}}
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach($locales as $i => $locale)
                                <li class="nav-item">
                                    <button class="nav-link {{ $i == 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                            data-bs-target="#name-{{ $locale }}" type="button" role="tab">
                                        {{ strtoupper($locale) }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content border border-top-0 p-3 mb-3">
                            @foreach($locales as $i => $locale)
                                <div class="tab-pane fade {{ $i == 0 ? 'show active' : '' }}" id="name-{{ $locale }}" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('messages.form.name') }} ({{ strtoupper($locale) }}) *</label>
                                        <input type="text" name="name[{{ $locale }}]" class="form-control @error("name.{$locale}") is-invalid @enderror"
                                               value="{{ old("name.{$locale}", $giftCard->name[$locale] ?? '') }}" required>
                                        @error("name.{$locale}") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('messages.common.description') }} ({{ strtoupper($locale) }})</label>
                                        <textarea name="description[{{ $locale }}]" class="form-control" rows="4">{{ old("description.{$locale}", $giftCard->description[$locale] ?? '') }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $giftCard->is_active))>
                                    <label class="form-check-label" for="is_active">{{ __('messages.form.active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> {{ __('messages.Enregistrer') }}
                </button>
            </form>
        </div>

        {{-- Onglet Catégories --}}
        <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <h5>{{ __('messages.product.categories') }}</h5>
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_category') }}
            </button>

            <ul class="list-group mb-3">
                @forelse($giftCard->categories ?? [] as $category)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $category->fullPathName() }}
                        <form action="{{ route('gift-cards.categories.detach', [$giftCard, $category]) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('messages.product.no_category') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

{{-- Modal Ajout Catégorie --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gift-cards.categories.attach', $giftCard) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.product.add_category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('messages.product.select_category') }}</label>
                <select name="category_id" class="form-select" required>
                    <option value="">--</option>
                    @foreach($allCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->fullPathName() }}</option>
                        @foreach($cat->children as $child)
                            <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;→ {{ $child->fullPathName() }}</option>
                            @foreach($child->children as $subChild)
                                <option value="{{ $subChild->id }}">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ {{ $subChild->fullPathName() }}</option>
                            @endforeach
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('messages.btn.add') }}</button>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">{{ __('messages.flash.success') }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif

@endsection
