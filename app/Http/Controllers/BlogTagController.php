<?php

namespace App\Http\Controllers;

use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogTagController extends Controller
{
    public function index()
    {
        $tags = BlogTag::withCount('posts')
                       ->orderBy('created_at', 'desc')
                       ->paginate(20);

        return view('blog.tags.index', compact('tags'));
    }

    public function create()
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        return view('blog.tags.create', compact('locales'));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
        ]);

        $tag = new BlogTag();

        // Translations
        foreach ($locales as $locale) {
            $tag->setTranslation('name', $locale, $data['name'][$locale] ?? '');
            $tag->setTranslation('slug', $locale, Str::slug($data['name'][$locale] ?? ''));
        }

        $tag->save();

        return redirect()->route('blog.tags.index')
                        ->with('success', __('messages.blog_tag.created'));
    }

    public function edit(BlogTag $tag)
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        return view('blog.tags.edit', compact('tag', 'locales'));
    }

    public function update(Request $request, BlogTag $tag)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
        ]);

        // Translations
        foreach ($locales as $locale) {
            $tag->setTranslation('name', $locale, $data['name'][$locale] ?? '');
            $tag->setTranslation('slug', $locale, Str::slug($data['name'][$locale] ?? ''));
        }

        $tag->save();

        return redirect()->route('blog.tags.index')
                        ->with('success', __('messages.blog_tag.updated'));
    }

    public function destroy(BlogTag $tag)
    {
        $tag->delete();

        return redirect()->route('blog.tags.index')
                        ->with('success', __('messages.blog_tag.deleted'));
    }
}
