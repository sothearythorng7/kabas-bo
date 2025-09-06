<div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.category.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    @php
                        $locales = config('app.website_locales');
                        $translations = $category->translations ?? collect();
                    @endphp
                    <ul class="nav nav-tabs" id="editCategoryLocalesTab{{ $category->id }}" role="tablist">
                        @foreach($locales as $index => $locale)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($index===0) active @endif"
                                        id="edit-{{ $category->id }}-{{ $locale }}-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#edit-{{ $category->id }}-{{ $locale }}"
                                        type="button" role="tab">
                                    {{ strtoupper($locale) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content mt-3">
                        @foreach($locales as $index => $locale)
                            <div class="tab-pane fade @if($index===0) show active @endif"
                                 id="edit-{{ $category->id }}-{{ $locale }}" role="tabpanel">
                                <div class="mb-3">
                                    <label>{{ __('messages.category.name') }} ({{ strtoupper($locale) }})</label>
                                    <input type="text" name="name[{{ $locale }}]" class="form-control"
                                           value="{{ $translations->firstWhere('locale', $locale)?->name }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
