@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Hero Slides</h1>
  <a href="{{ route('hero-slides.create') }}" class="btn btn-success mb-3">Ajouter</a>
  <table class="table align-middle">
    <thead><tr><th>#</th><th>Aperçu</th><th>Actif</th><th>Ordre</th><th></th></tr></thead>
    <tbody>
      @foreach($slides as $s)
      <tr>
        <td class="text-muted">{{ $s->id }}</td>
        <td>@if($s->image_path)<img src="{{ asset('storage/'.$s->image_path) }}" style="height:60px">@endif</td>
        <td>{!! $s->is_active ? '<span class="badge bg-success">on</span>' : '<span class="badge bg-secondary">off</span>' !!}</td>
        <td>{{ $s->sort_order }}</td>
        <td class="text-end">
          <a href="{{ route('hero-slides.edit', $s) }}" class="btn btn-sm btn-primary">Modifier</a>
          <form action="{{ route('hero-slides.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-danger">Supprimer</button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
