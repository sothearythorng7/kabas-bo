<h5>{{ __('messages.product.categories') }}</h5>
<ul class="list-group mb-3">
    @forelse($product->categories ?? [] as $category)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            {{ $category->fullPathName() }}
            <form action="{{ route('products.categories.detach', [$product, $category]) }}" method="POST" class="m-0">
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
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
    <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_category') }}
</button>
@include('products.partials.modal-add-category')
