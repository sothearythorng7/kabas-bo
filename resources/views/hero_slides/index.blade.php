@extends('layouts.app')
@section('content')
<div class="container mt-4">
  <h1 class="crud_title">@t('hero_slide.title')</h1>
  <a href="{{ route('hero-slides.create') }}" class="btn btn-success mb-3">
    <i class="bi bi-plus-circle"></i> @t('hero_slide.add')
  </a>
  <table class="table table-hover align-middle">
    <thead>
      <tr>
        <th>@t('hero_slide.id')</th>
        <th>@t('hero_slide.preview')</th>
        <th>@t('hero_slide.active')</th>
        <th>@t('hero_slide.sort_order')</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @foreach($slides as $s)
      <tr>
        <td class="text-muted">{{ $s->id }}</td>
        <td>
          @if($s->image_path)
            <img src="{{ asset('storage/'.$s->image_path) }}" style="height:60px" class="rounded">
          @endif
        </td>
        <td>
          @if($s->is_active)
            <span class="badge bg-success">@t('hero_slide.on')</span>
          @else
            <span class="badge bg-secondary">@t('hero_slide.off')</span>
          @endif
        </td>
        <td>{{ $s->sort_order }}</td>
        <td class="text-end">
          <a href="{{ route('hero-slides.edit', $s) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil"></i> @t('hero_slide.edit')
          </a>
          <form action="{{ route('hero-slides.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('@t('hero_slide.delete_confirm')')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">
              <i class="bi bi-trash"></i> @t('hero_slide.delete')
            </button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
