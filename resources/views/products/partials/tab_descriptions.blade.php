<form action="{{ route('products.descriptions.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    @php $i=0; @endphp
    <ul class="nav nav-tabs" role="tablist">
        @foreach($locales as $locale)
            <li class="nav-item">
                <button class="nav-link @if($i===0) active @endif" data-bs-toggle="tab" data-bs-target="#desc-{{ $locale }}" type="button">{{ strtoupper($locale) }}</button>
            </li>
            @php $i++; @endphp
        @endforeach
    </ul>
    <div class="tab-content mt-3">
        @php $i=0; @endphp
        @foreach($locales as $locale)
            <div class="tab-pane fade @if($i===0) show active @endif" id="desc-{{ $locale }}">
                <textarea name="description[{{ $locale }}]" class="form-control" rows="6">{{ old("description.$locale", $product->description[$locale] ?? '') }}</textarea>
            </div>
            @php $i++; @endphp
        @endforeach
    </div>
    <div class="mt-3">
        <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </div>
</form>
