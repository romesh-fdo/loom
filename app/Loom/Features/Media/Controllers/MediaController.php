<?php

namespace Loom\Features\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        session(['loom.file_manager.disk' => 'media']);

        return view('loom-media::index');
    }
}
