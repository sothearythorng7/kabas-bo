<div class="mb-3">
    <label>@t('Nom') *</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label>@t('Couleur') *</label>
    <input type="color" name="color" class="form-control" value="{{ old('color', $category->color ?? '#6c757d') }}" required>
    <small class="form-text text-muted">@t('Choisissez une couleur pour identifier visuellement cette catégorie')</small>
</div>
