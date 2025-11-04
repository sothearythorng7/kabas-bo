@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Nouveau slide</h1>
  <form action="{{ route('hero-slides.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
      <label class="form-label">Image (jpg/png/webp)</label>
      <input type="file" name="image" class="form-control" required>
    </div>
    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label">Ordre</label>
        <input type="number" name="sort_order" class="form-control" value="0">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Actif</label><br>
        <input type="checkbox" name="is_active" value="1" checked>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Début</label>
        <input type="datetime-local" name="starts_at" class="form-control">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Fin</label>
        <input type="datetime-local" name="ends_at" class="form-control">
      </div>
    </div>
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('hero-slides.index') }}" class="btn btn-secondary">Annuler</a>
  </form>
</div>
@endsection
