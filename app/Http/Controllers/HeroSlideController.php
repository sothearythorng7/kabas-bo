<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index() {
        $slides = HeroSlide::orderBy('sort_order')->orderBy('id')->get();
        return view('hero_slides.index', compact('slides'));
    }

    public function create() { return view('hero_slides.create'); }

    public function store(Request $request) {
        $data = $request->validate([
            'image'      => ['required','file','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'sort_order' => ['nullable','integer'],
            'is_active'  => ['sometimes','boolean'],
            'starts_at'  => ['nullable','date'],
            'ends_at'    => ['nullable','date','after_or_equal:starts_at'],
        ]);

        $imagePath = $request->file('image')->store('hero_slides', 'public');
        HeroSlide::create([
            'image_path' => $imagePath,
            'sort_order' => $request->integer('sort_order', 0),
            'is_active'  => (bool)$request->boolean('is_active', true),
            'starts_at'  => $request->input('starts_at'),
            'ends_at'    => $request->input('ends_at'),
        ]);

        $this->publishJson();
        return redirect()->route('hero-slides.index')->with('success', __('messages.hero_slide.created'));
    }

    public function edit(HeroSlide $heroSlide) {
        return view('hero_slides.edit', compact('heroSlide'));
    }

    public function update(Request $request, HeroSlide $heroSlide) {
        $data = $request->validate([
            'image'      => ['sometimes','file','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'sort_order' => ['nullable','integer'],
            'is_active'  => ['sometimes','boolean'],
            'starts_at'  => ['nullable','date'],
            'ends_at'    => ['nullable','date','after_or_equal:starts_at'],
        ]);

        if ($request->hasFile('image')) {
            if ($heroSlide->image_path && Storage::disk('public')->exists($heroSlide->image_path)) {
                Storage::disk('public')->delete($heroSlide->image_path);
            }
            $heroSlide->image_path = $request->file('image')->store('hero_slides', 'public');
        }

        $heroSlide->sort_order = $request->integer('sort_order', $heroSlide->sort_order);
        $heroSlide->is_active  = (bool)$request->boolean('is_active', $heroSlide->is_active);
        $heroSlide->starts_at  = $request->input('starts_at');
        $heroSlide->ends_at    = $request->input('ends_at');
        $heroSlide->save();

        $this->publishJson();
        return redirect()->route('hero-slides.index')->with('success', __('messages.hero_slide.updated'));
    }

    public function destroy(HeroSlide $heroSlide) {
        if ($heroSlide->image_path && Storage::disk('public')->exists($heroSlide->image_path)) {
            Storage::disk('public')->delete($heroSlide->image_path);
        }
        $heroSlide->delete();
        $this->publishJson();
        return back()->with('success', __('messages.hero_slide.deleted'));
    }

    private function publishJson(): void {
        $slides = HeroSlide::published()->get()->map(fn($s) => [
            'image_url' => asset('storage/'.$s->image_path),
            'sort_order'=> $s->sort_order,
        ])->values();

        $targetDir  = rtrim(config('site_paths.public_path', '/var/www/kabas-site/public'), '/').'/data';
        $targetFile = $targetDir.'/hero_slides.json';

        File::ensureDirectoryExists($targetDir, 0755, true);
        File::put($targetFile, json_encode(['slides'=>$slides], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }
}
