@extends('layouts.app')
@section('content')
<div class="container mt-4">
  <h1 class="crud_title">{{ __('messages.hero_slide.title') }}</h1>
  <a href="{{ route('hero-slides.create') }}" class="btn btn-success mb-3">
    <i class="bi bi-plus-circle"></i> {{ __('messages.hero_slide.add') }}
  </a>
  <table class="table table-hover align-middle">
    <thead>
      <tr>
        <th>{{ __('messages.hero_slide.id') }}</th>
        <th>{{ __('messages.hero_slide.preview') }}</th>
        <th>{{ __('messages.hero_slide.active') }}</th>
        <th>{{ __('messages.hero_slide.sort_order') }}</th>
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
            <span class="badge bg-success">{{ __('messages.hero_slide.on') }}</span>
          @else
            <span class="badge bg-secondary">{{ __('messages.hero_slide.off') }}</span>
          @endif
        </td>
        <td>{{ $s->sort_order }}</td>
        <td class="text-end">
          <a href="{{ route('hero-slides.edit', $s) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil"></i> {{ __('messages.hero_slide.edit') }}
          </a>
          <form action="{{ route('hero-slides.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.hero_slide.delete_confirm') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">
              <i class="bi bi-trash"></i> {{ __('messages.hero_slide.delete') }}
            </button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
