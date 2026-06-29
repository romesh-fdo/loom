<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Loom\Support\ThemeManager;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function settings(ThemeManager $themes): View
    {
        return view('admin.settings', [
            'themes' => $themes->all(),
            'activeTheme' => $themes->activeSlug(),
            'activeTab' => request('tab', 'theme'),
        ]);
    }
}
