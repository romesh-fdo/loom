<?php

namespace Loom\Features\Assets\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AssetsController extends Controller
{
    public function index(): View
    {
        return view('loom-assets::index', [
            'assetsUrl' => url($this->assetsPublicPath()),
        ]);
    }

    protected function assetsPublicPath(): string
    {
        return trim((string) config('loom.assets.public_path', 'theme/assets'), '/');
    }
}
