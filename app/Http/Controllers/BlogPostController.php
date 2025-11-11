<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['category', 'author', 'tags']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title->fr', 'like', "%{$search}%")
                  ->orWhere('title->en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('blog_category_id', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->published();
            } else {
                $query->draft();
            }
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(20);
        $categories = BlogCategory::orderBy('name->fr')->get();

        return view('blog.posts.index', compact('posts', 'categories'));
    }

    public function create()
    {
        $categories = BlogCategory::active()->get();
        $tags = BlogTag::orderBy('name->fr')->get();
        $locales = config('app.website_locales', ['fr', 'en']);

        return view('blog.posts.create', compact('categories', 'tags', 'locales'));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'title' => 'required|array',
            'title.*' => 'required|string',
            'excerpt' => 'nullable|array',
            'content' => 'required|array',
            'content.*' => 'required|string',
            'featured_image' => 'nullable|image|max:4096',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|array',
            'meta_description' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        $post = new BlogPost();
        $post->blog_category_id = $data['blog_category_id'] ?? null;
        $post->user_id = auth()->id();
        $post->is_published = $request->boolean('is_published');
        $post->published_at = $data['published_at'] ?? ($post->is_published ? now() : null);
        $post->views_count = 0;

        // Translations
        foreach ($locales as $locale) {
            $post->setTranslation('title', $locale, $data['title'][$locale] ?? '');
            $post->setTranslation('slug', $locale, Str::slug($data['title'][$locale] ?? ''));
            $post->setTranslation('excerpt', $locale, $data['excerpt'][$locale] ?? '');
            $post->setTranslation('content', $locale, $data['content'][$locale] ?? '');
            $post->setTranslation('meta_title', $locale, $data['meta_title'][$locale] ?? '');
            $post->setTranslation('meta_description', $locale, $data['meta_description'][$locale] ?? '');
        }

        // Upload image
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('blog', 'public');
            $post->featured_image = $path;
        }

        $post->save();

        // Sync tags
        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return redirect()->route('blog.posts.index')
                        ->with('success', __('messages.blog_post.created'));
    }

    public function show(BlogPost $post)
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        return view('blog.posts.show', compact('post', 'locales'));
    }

    public function edit(BlogPost $post)
    {
        $categories = BlogCategory::active()->get();
        $tags = BlogTag::orderBy('name->fr')->get();
        $locales = config('app.website_locales', ['fr', 'en']);

        return view('blog.posts.edit', compact('post', 'categories', 'tags', 'locales'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'title' => 'required|array',
            'title.*' => 'required|string',
            'excerpt' => 'nullable|array',
            'content' => 'required|array',
            'content.*' => 'required|string',
            'featured_image' => 'nullable|image|max:4096',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|array',
            'meta_description' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id',
        ]);

        $post->blog_category_id = $data['blog_category_id'] ?? null;
        $post->is_published = $request->boolean('is_published');

        // Set published_at only if publishing for the first time
        if ($post->is_published && !$post->published_at) {
            $post->published_at = $data['published_at'] ?? now();
        }

        // Translations
        foreach ($locales as $locale) {
            $post->setTranslation('title', $locale, $data['title'][$locale] ?? '');
            $post->setTranslation('slug', $locale, Str::slug($data['title'][$locale] ?? ''));
            $post->setTranslation('excerpt', $locale, $data['excerpt'][$locale] ?? '');
            $post->setTranslation('content', $locale, $data['content'][$locale] ?? '');
            $post->setTranslation('meta_title', $locale, $data['meta_title'][$locale] ?? '');
            $post->setTranslation('meta_description', $locale, $data['meta_description'][$locale] ?? '');
        }

        // Upload new image
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $path = $request->file('featured_image')->store('blog', 'public');
            $post->featured_image = $path;
        }

        $post->save();

        // Sync tags
        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return redirect()->route('blog.posts.index')
                        ->with('success', __('messages.blog_post.updated'));
    }

    public function destroy(BlogPost $post)
    {
        // Delete image
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        return redirect()->route('blog.posts.index')
                        ->with('success', __('messages.blog_post.deleted'));
    }

    public function deleteImage(BlogPost $post)
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
            $post->featured_image = null;
            $post->save();
        }

        return back()->with('success', __('messages.blog_post.image_deleted'));
    }
}
