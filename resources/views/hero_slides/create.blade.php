@extends('layouts.app')
@section('content')
<div class="container mt-4">
  <h1 class="crud_title">@t('hero_slide.title_create')</h1>
  <form action="{{ route('hero-slides.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
      <label class="form-label">@t('hero_slide.image')</label>
      <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" required accept="image/*">
      @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label">@t('hero_slide.order_label')</label>
        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}">
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">@t('hero_slide.active_label')</label><br>
        <div class="form-check form-switch">
          <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_active">@t('hero_slide.active')</label>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">@t('hero_slide.starts_at')</label>
        <input type="datetime-local" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at') }}">
        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">@t('hero_slide.ends_at')</label>
        <input type="datetime-local" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at') }}">
        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
    <button type="submit" class="btn btn-success">
      <i class="bi bi-save"></i> @t('hero_slide.save')
    </button>
    <a href="{{ route('hero-slides.index') }}" class="btn btn-secondary">
      <i class="bi bi-x-circle"></i> @t('hero_slide.cancel')
    </a>
  </form>
</div>
@endsection
