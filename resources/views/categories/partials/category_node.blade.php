<li class="category-item">
    <div class="category-label">
        {{-- Flèche (icône Bootstrap) --}}
        @if($category->children->count())
            <i class="bi bi-chevron-right toggle-arrow"></i>
        @else
            <span class="toggle-arrow" style="display:inline-block;width:1em;"></span>
        @endif

        <span class="category-name">{{ $category->translation()?->name ?? '—' }}</span>

        <div class="ms-auto d-flex gap-1">
            {{-- Edit: type="button" + data-bs-target vers modal plate (ID unique) --}}
            <button type="button" class="btn btn-sm btn-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#editCategoryModal-{{ $category->id }}">
                {{ __('messages.btn.edit') ?? 'Edit' }}
            </button>

            <form action="{{ route('categories.destroy', $category) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('{{ __('messages.category.confirm_delete') }}')">
                    {{ __('messages.btn.delete') ?? 'Delete' }}
                </button>
            </form>
        </div>
    </div>

    @if($category->children->count())
        <ul class="category-children list-unstyled">
            @foreach($category->children as $child)
                @include('categories.partials.category_node', ['category' => $child, 'allCategories' => $allCategories])
            @endforeach
        </ul>
    @endif
</li>
