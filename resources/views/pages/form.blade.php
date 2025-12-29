@extends('layouts.app')

@section('content')
@php($locales = $locales ?? config('app.website_locales', ['en']))

<div class="container py-4">
    <h1 class="h3 crud_title">{{ $page->exists ? __('messages.page.title_edit') : __('messages.page.title_create') }}</h1>

    <form method="POST"
          action="{{ $page->exists ? route('admin.pages.update',$page) : route('admin.pages.store') }}">
        @csrf
        @if($page->exists) @method('PUT') @endif

        {{-- Onglets de langues --}}
        <ul class="nav nav-tabs mb-3" role="tablist">
            @foreach($locales as $i => $loc)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if($i===0) active @endif"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-{{ $loc }}"
                        type="button" role="tab">
                    {{ strtoupper($loc) }}
                </button>
            </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach($locales as $i => $loc)
            <div class="tab-pane fade @if($i===0) show active @endif"
                 id="tab-{{ $loc }}" role="tabpanel">

                <div class="mb-3">
                    <label class="form-label">{{ __('messages.page.title_label') }} ({{ strtoupper($loc) }})</label>
                    <input type="text"
                           name="title[{{ $loc }}]"
                           class="form-control"
                           value="{{ old("title.$loc", $page->title[$loc] ?? '') }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('messages.page.slug') }} ({{ strtoupper($loc) }})</label>
                    <input type="text"
                           name="slugs[{{ $loc }}]"
                           class="form-control"
                           value="{{ old("slugs.$loc", $page->slugs[$loc] ?? '') }}"
                           placeholder="{{ __('messages.page.slug_placeholder') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('messages.page.content') }} ({{ strtoupper($loc) }})</label>
                    <textarea name="content[{{ $loc }}]"
                              class="form-control tinymce-text"
                              rows="12">{{ old("content.$loc", $page->content[$loc] ?? '') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('messages.page.meta_title') }} ({{ strtoupper($loc) }})</label>
                    <input type="text"
                           name="meta_title[{{ $loc }}]"
                           class="form-control"
                           value="{{ old("meta_title.$loc", $page->meta_title[$loc] ?? '') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('messages.page.meta_description') }} ({{ strtoupper($loc) }})</label>
                    <textarea name="meta_description[{{ $loc }}]"
                              class="form-control" rows="2">{{ old("meta_description.$loc", $page->meta_description[$loc] ?? '') }}</textarea>
                </div>
            </div>
            @endforeach
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input"
                   type="checkbox"
                   name="is_published"
                   id="is_published"
                   value="1"
                   @checked(old('is_published', $page->is_published))>
            <label class="form-check-label" for="is_published">{{ __('messages.page.is_published') }}</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> {{ __('messages.page.save') }}
            </button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.page.cancel') }}
            </a>
        </div>
    </form>
</div>

@push('scripts')
{{-- TinyMCE texte uniquement (gratuit, sans image) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js" referrerpolicy="origin"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  tinymce.init({
    selector: 'textarea.tinymce-text',
    height: 420,
    menubar: false,
    branding: false,

    plugins: 'lists link code wordcount autoresize',
    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | alignleft aligncenter alignright | link | removeformat | code',

    style_formats: [
      { title: 'Titre section', block: 'h2' },
      { title: 'Sous-titre', block: 'h3' },
      { title: 'Paragraphe', block: 'p' },
    ],

    paste_as_text: true,
    paste_data_images: false,
    invalid_elements: 'img,figure,video,audio,iframe,embed,object,svg,canvas,table,thead,tbody,tfoot,tr,td,th,hr,source,track',
    valid_elements: 'p,h2,h3,strong/b,em/i,u,a[href|target|title],ul,ol,li,blockquote,code,pre,br,span[class]',

    setup(editor) {
      editor.on('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt && (dt.files?.length || Array.from(dt.items||[]).some(i => i.type?.startsWith('image/')))) {
          e.preventDefault();
        }
      });
    },

    content_style: `
      body { font-family: -apple-system, Segoe UI, Roboto, Inter, sans-serif; line-height:1.6; }
      h2 { font-size: 1.4rem; margin: 1rem 0 .5rem; }
      h3 { font-size: 1.2rem; margin: .85rem 0 .35rem; }
      p, li { font-size: 1rem; }
      a { text-decoration: underline; }
    `,
    forced_root_block: 'p'
  });
});
</script>
@endpush
@endsection
