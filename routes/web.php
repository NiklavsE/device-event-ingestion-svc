<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => config('app.name'),
    'docs' => url('/docs'),
    'health' => url('/healthz'),
]));

Route::get('/docs', fn () => view('docs.swagger'));

Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');
    abort_unless(is_file($path), 404);

    return response()->file($path, ['Content-Type' => 'application/yaml']);
});
