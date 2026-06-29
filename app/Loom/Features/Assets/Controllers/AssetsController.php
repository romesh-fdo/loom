<?php

namespace Loom\Features\Assets\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Loom\Support\ThemeManager;

class AssetsController extends Controller
{
    public function index(): View
    {
        $themeManager = app(ThemeManager::class);
        $activeThemeSlug = $themeManager->activeSlug();

        return view('loom-assets::index', [
            'assetsUrl' => url($this->assetsPublicPath()),
            'activeTheme' => $themeManager->find($activeThemeSlug),
            'activeThemeSlug' => $activeThemeSlug,
        ]);
    }

    protected function assetsPublicPath(): string
    {
        return trim((string) config('loom.assets.public_path', 'theme/assets'), '/');
    }
}
