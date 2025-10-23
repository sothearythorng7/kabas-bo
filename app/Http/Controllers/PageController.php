<?php

// app/Http/Controllers/Admin/PageController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $req)
    {
        $q = Page::query();

        if ($req->filled('s')) {
            $s = $req->string('s');
            $q->where(function($qq) use ($s) {
                $qq->where('title->fr','like',"%$s%")
                   ->orWhere('title->en','like',"%$s%")
                   ->orWhere('slugs->fr','like',"%$s%")
                   ->orWhere('slugs->en','like',"%$s%");
            });
        }
        if ($req->filled('published')) {
            $q->where('is_published', $req->boolean('published'));
        }

        $pages = $q->orderByDesc('published_at')->orderBy('id','desc')
                   ->paginate(25)->withQueryString();

        return view('pages.index', compact('pages'));
    }

    public function create()
    {
        $locales = config('app.website_locales', ['en']);
        return view('pages.form', ['page' => new Page(), 'locales' => $locales]);
    }

    public function store(Request $r)
    {
        $locales = config('app.website_locales', ['en']);

        $data = $r->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'content' => 'nullable|array',
            'content.*' => 'nullable|string',
            'slugs' => 'nullable|array',        // si vide, on génère
            'slugs.*' => 'nullable|string|max:255',
            'meta_title' => 'nullable|array',
            'meta_title.*' => 'nullable|string|max:255',
            'meta_description' => 'nullable|array',
            'meta_description.*' => 'nullable|string|max:500',
            'is_published' => 'sometimes|boolean',
        ]);

        $page = new Page();
        foreach ($locales as $loc) {
            $page->setTranslation('title', $loc, $data['title'][$loc] ?? '');
            $page->setTranslation('content', $loc, $data['content'][$loc] ?? '');
            $slug = $data['slugs'][$loc] ?? Str::slug($data['title'][$loc] ?? '');
            $page->setTranslation('slugs', $loc, $slug);
            $page->setTranslation('meta_title', $loc, $data['meta_title'][$loc] ?? null);
            $page->setTranslation('meta_description', $loc, $data['meta_description'][$loc] ?? null);
        }
        $page->is_published = (bool)($data['is_published'] ?? false);
        $page->published_at = $page->is_published ? now() : null;
        $page->save();

        return redirect()->route('admin.pages.index')->with('success', __('messages.common.created'));
    }

    public function edit(Page $page)
    {
        $locales = config('app.website_locales', ['en']);
        return view('pages.form', compact('page','locales'));
    }

    public function update(Request $r, Page $page)
    {
        $locales = config('app.website_locales', ['en']);

        $data = $r->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'content' => 'nullable|array',
            'content.*' => 'nullable|string',
            'slugs' => 'nullable|array',
            'slugs.*' => 'nullable|string|max:255',
            'meta_title' => 'nullable|array',
            'meta_title.*' => 'nullable|string|max:255',
            'meta_description' => 'nullable|array',
            'meta_description.*' => 'nullable|string|max:500',
            'is_published' => 'sometimes|boolean',
        ]);

        foreach ($locales as $loc) {
            $page->setTranslation('title', $loc, $data['title'][$loc] ?? '');
            $page->setTranslation('content', $loc, $data['content'][$loc] ?? '');
            $slug = $data['slugs'][$loc] ?? Str::slug($data['title'][$loc] ?? '');
            $page->setTranslation('slugs', $loc, $slug);
            $page->setTranslation('meta_title', $loc, $data['meta_title'][$loc] ?? null);
            $page->setTranslation('meta_description', $loc, $data['meta_description'][$loc] ?? null);
        }

        $newPublished = (bool)($data['is_published'] ?? false);
        if ($newPublished && !$page->is_published) {
            $page->published_at = now();
        }
        if (!$newPublished) {
            $page->published_at = null;
        }
        $page->is_published = $newPublished;

        $page->save();

        return redirect()->route('pages.index')->with('success', __('messages.common.updated'));
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return back()->with('success', __('messages.common.deleted'));
    }

    public function toggle(Page $page)
    {
        $page->is_published = !$page->is_published;
        $page->published_at = $page->is_published ? now() : null;
        $page->save();

        return back()->with('success', __('messages.common.updated'));
    }
}
