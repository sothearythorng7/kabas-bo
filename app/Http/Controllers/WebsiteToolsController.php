<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class WebsiteToolsController extends Controller
{
    public function index()
    {
        $products = Product::all(['id', 'name', 'slugs', 'seo_title', 'meta_description', 'ean', 'is_active']);
        $locales = ['en', 'fr'];

        $totalProducts = $products->count();
        $activeProducts = $products->where('is_active', true)->count();

        $missingSlugCount = 0;
        $emptyNameCount = 0;
        $missingSeoCount = 0;
        $problems = [];

        foreach ($products as $product) {
            $names = $product->name ?? [];
            $slugs = $product->slugs ?? [];
            $seoTitles = $product->seo_title ?? [];
            $metaDescs = $product->meta_description ?? [];

            foreach ($locales as $loc) {
                $slug = $slugs[$loc] ?? '';
                $name = $names[$loc] ?? '';

                if (empty($slug)) {
                    $missingSlugCount++;
                    $productName = $names['en'] ?? $names['fr'] ?? "ID:{$product->id}";
                    $problems[] = [
                        'id' => $product->id,
                        'name' => $productName,
                        'locale' => $loc,
                        'issue' => 'slug_missing',
                        'is_active' => $product->is_active,
                    ];
                }

                if (empty($seoTitles[$loc] ?? '') || empty($metaDescs[$loc] ?? '')) {
                    $missingSeoCount++;
                }

                if (isset($names[$loc]) && \Illuminate\Support\Str::slug($name) === '' && !empty($slug)) {
                    $emptyNameCount++;
                }
            }
        }

        return view('website-tools.index', compact(
            'totalProducts',
            'activeProducts',
            'missingSlugCount',
            'emptyNameCount',
            'missingSeoCount',
            'problems'
        ));
    }

    public function generateSeo(Request $request)
    {
        $dryRun = $request->has('dry_run');
        $force = $request->has('force');

        $args = [];
        if ($dryRun) $args['--dry-run'] = true;
        if ($force) $args['--force'] = true;

        Artisan::call('products:generate-seo', $args);
        $output = Artisan::output();

        return back()->with('command_output', $output)->with(
            $dryRun ? 'info' : 'success',
            $dryRun
                ? __('messages.website_tools.dry_run_complete')
                : __('messages.website_tools.seo_generated')
        );
    }

    public function fixSlugs(Request $request)
    {
        $dryRun = $request->has('dry_run');

        $args = $dryRun ? ['--dry-run' => true] : [];
        Artisan::call('products:fix-slugs', $args);

        $output = Artisan::output();

        return back()->with('command_output', $output)->with(
            $dryRun ? 'info' : 'success',
            $dryRun
                ? __('messages.website_tools.dry_run_complete')
                : __('messages.website_tools.slugs_fixed')
        );
    }
}
