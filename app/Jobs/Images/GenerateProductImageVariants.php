<?php

namespace App\Jobs\Images;

use App\Models\ProductImage;
use App\Services\Images\ImageVariantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductImageVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    /**
     * @param int $productImageId
     * @param array|null $sizes Restrict to specific variant sizes (null = all configured)
     */
    public function __construct(
        public int $productImageId,
        public ?array $sizes = null,
    ) {
        $this->onQueue(config('images.queue', 'images'));
    }

    public function handle(ImageVariantService $service): void
    {
        $image = ProductImage::find($this->productImageId);
        if (!$image) {
            Log::warning('GenerateProductImageVariants: image not found', [
                'product_image_id' => $this->productImageId,
            ]);
            return;
        }

        $service->generate($image, $this->sizes);
    }
}
