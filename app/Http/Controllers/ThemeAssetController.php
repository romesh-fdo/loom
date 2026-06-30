<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThemeAssetController extends Controller
{
    public function show(string $theme, string $path): Response
    {
        $assetsRoot = realpath(base_path('theme/'.$theme.'/assets'));

        if ($assetsRoot === false) {
            throw new NotFoundHttpException;
        }

        $filePath = realpath($assetsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));

        if ($filePath === false || ! str_starts_with($filePath, $assetsRoot) || ! is_file($filePath)) {
            throw new NotFoundHttpException;
        }

        return response()->file($filePath, [
            'Content-Type' => File::mimeType($filePath) ?: 'application/octet-stream',
        ]);
    }
}
