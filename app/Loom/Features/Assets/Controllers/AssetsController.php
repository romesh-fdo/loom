<?php

namespace Loom\Features\Assets\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AssetsController extends Controller
{
    public function index(): View
    {
        session(['loom.file_manager.disk' => 'assets']);

        return view('loom-assets::index');
    }
}
