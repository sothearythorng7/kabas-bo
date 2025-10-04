@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.category.title') }}</h1>
    {{-- Bouton Ajouter catégorie --}}
    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.category.btnCreate') }}
    </button>

    {{-- Arbre catégories --}}
    <ul class="list-unstyled">
        @foreach($categories as $category)
            @include('categories.partials.category_node', ['category' => $category])
        @endforeach
    </ul>
</div>

{{-- Modal Ajouter catégorie --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.btn.add') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Parent --}}
                    <div class="mb-3">
                        <label>{{ __('messages.category.parent') }}</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- {{ __('messages.category.root') }} --</option>
                            @foreach($allCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->fullPathName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Onglets par locale --}}
                    @php $locales = config('app.website_locales', ['en']); @endphp
                    <ul class="nav nav-tabs" id="addCategoryLocalesTab" role="tablist">
                        @foreach($locales as $index => $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($index===0) active @endif"
                                        id="add-{{ $locale }}-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#add-{{ $locale }}"
                                        type="button" role="tab">
                                    {{ strtoupper($locale) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content mt-3">
                        @foreach($locales as $index => $locale)
                            <div class="tab-pane fade @if($index===0) show active @endif" id="add-{{ $locale }}" role="tabpanel">
                                <div class="mb-3">
                                    <label>{{ __('messages.category.name') }} ({{ strtoupper($locale) }})</label>
                                    <input type="text" name="name[{{ $locale }}]" class="form-control">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- UNE SEULE Modal d'édition dynamique --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">{{ __('messages.category.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Parent --}}
                    <div class="mb-3">
                        <label>{{ __('messages.category.parent') }}</label>
                        <select name="parent_id" id="editParentSelect" class="form-select">
                            <option value="">-- {{ __('messages.category.root') }} --</option>
                            @foreach($allCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->fullPathName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Onglets par locale --}}
                    @php $locales = config('app.website_locales', ['en']); @endphp
                    <ul class="nav nav-tabs" id="editCategoryLocalesTab" role="tablist">
                        @foreach($locales as $index => $locale)
                            <li class="nav-item" role="presentation">
                                <button type="button"
                                        class="nav-link @if($index===0) active @endif"
                                        id="edit-{{ $locale }}-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#edit-{{ $locale }}"
                                        role="tab">
                                    {{ strtoupper($locale) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content mt-3">
                        @foreach($locales as $index => $locale)
                            <div class="tab-pane fade @if($index===0) show active @endif"
                                 id="edit-{{ $locale }}"
                                 role="tabpanel">
                                <div class="mb-3">
                                    <label>{{ __('messages.category.name') }} ({{ strtoupper($locale) }})</label>
                                    <input type="text"
                                           name="name[{{ $locale }}]"
                                           id="editName{{ ucfirst($locale) }}"
                                           class="form-control">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.category-item { list-style: none; padding-left: 0; margin-bottom: 4px; }
.category-label { display:flex; align-items:center; cursor:pointer; padding:4px; border-radius:3px; }
.category-label:hover { background-color: #f8f9fa; }
.toggle-arrow { display:inline-block; width:1em; margin-right:0.5rem; color:black; transition: transform .15s ease; }
.toggle-arrow.rotated { transform: rotate(90deg); }
.category-children { margin-left:1.2em; padding-left:0; display:none; }
.category-children.show { display:block; }
.category-name { margin-left:0.25rem; }
</style>
@endpush

@push('scripts')
<script>
// Données des catégories (JSON depuis PHP)
const categoriesData = @json($allCategories->map(function($cat) {
    return [
        'id' => $cat->id,
        'parent_id' => $cat->parent_id,
        'translations' => $cat->translations->keyBy('locale')->map(fn($t) => $t->name)
    ];
})->keyBy('id'));

document.addEventListener('DOMContentLoaded', function() {
    // Toggle tree nodes
    document.querySelectorAll('.category-item > .category-label').forEach(function(label) {
        label.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('form') || e.target.classList.contains('btn-close')) return;

            const parentLi = label.closest('.category-item');
            const childrenUl = parentLi.querySelector('.category-children');
            if (!childrenUl) return;

            childrenUl.classList.toggle('show');
            const arrow = label.querySelector('.toggle-arrow');
            if (arrow) arrow.classList.toggle('rotated');
        });
    });

    // Gérer l'ouverture de la modale d'édition
    document.querySelectorAll('.btn-edit-category').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryData = categoriesData[categoryId];
            
            if (!categoryData) return;

            // Mettre à jour l'action du formulaire
            document.getElementById('editCategoryForm').action = `/categories/${categoryId}`;
            
            // Mettre à jour le titre
            document.getElementById('editCategoryModalLabel').textContent = 
                '{{ __("messages.category.edit") }}: ' + (categoryData.translations.en || '—');
            
            // Mettre à jour le parent sélectionné
            const parentSelect = document.getElementById('editParentSelect');
            parentSelect.value = categoryData.parent_id || '';
            
            // Désactiver l'option de la catégorie courante (éviter boucle)
            Array.from(parentSelect.options).forEach(opt => {
                opt.disabled = (opt.value == categoryId);
            });
            
            // Mettre à jour les noms dans chaque locale
            @foreach(config('app.website_locales', ['en']) as $locale)
                document.getElementById('editName{{ ucfirst($locale) }}').value = 
                    categoryData.translations.{{ $locale }} || '';
            @endforeach
        });
    });
});
</script>
@endpush