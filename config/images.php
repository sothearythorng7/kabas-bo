<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product image variants
    |--------------------------------------------------------------------------
    |
    | Each variant defines a max width in pixels (height scales proportionally,
    | preserving aspect ratio). Variants are generated alongside the original
    | upload as sibling files using a suffix naming convention:
    |
    |   products/abc.jpg              ← original (untouched)
    |   products/abc-thumb.webp
    |   products/abc-thumb.jpg
    |   products/abc-medium.webp
    |   ...
    |
    | The `sync_on_upload` variant is generated synchronously in the upload
    | request to guarantee that listings always have a small image available
    | immediately. The rest are deferred to a queued job.
    |
    */

    'variants' => [
        'thumb'  => ['width' => 400],
        'medium' => ['width' => 800],
        'large'  => ['width' => 1600],
    ],

    'sync_on_upload' => 'thumb',

    'formats' => ['webp', 'jpg'],

    'quality' => [
        'webp' => 82,
        'jpg'  => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Variant generation toggle
    |--------------------------------------------------------------------------
    |
    | In the kabas-dev codebase, storage/app/public/products/ is a readonly
    | symlink to the prod storage. Writing variants there would fail. We use an
    | explicit env flag rather than APP_ENV checks because the prod .env
    | currently has APP_ENV=local (non-standard), so env-name based logic is
    | unreliable.
    |
    | Set IMAGE_VARIANTS_ENABLED=true in the prod .env.
    | Leave it false (or unset) in dev — uploads still work, urlFor() falls
    | back to the original path transparently.
    |
    */

    'enabled' => env('IMAGE_VARIANTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => 'images',

];
