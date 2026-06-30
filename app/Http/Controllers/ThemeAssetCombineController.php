<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Loom\Support\ThemeContent\ThemeAssetCombiner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThemeAssetCombineController extends Controller
{
    public function __construct(
        protected ThemeAssetCombiner $combiner,
    ) {}

    public function show(string $theme, string $signature, string $extension): Response
    {
        if (! in_array($extension, ['css', 'js'], true)) {
            throw new NotFoundHttpException;
        }

        $manifest = $this->combiner->manifest($signature);

        if ($manifest === null || $manifest['theme'] !== $theme) {
            throw new NotFoundHttpException;
        }

        $expectedType = $extension === 'js' ? 'script' : 'stylesheet';

        if ($manifest['type'] !== $expectedType) {
            throw new NotFoundHttpException;
        }

        $content = $this->combiner->render($theme, $manifest['type'], $manifest['paths']);

        return response($content, 200, [
            'Content-Type' => $extension === 'js' ? 'application/javascript' : 'text/css',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
