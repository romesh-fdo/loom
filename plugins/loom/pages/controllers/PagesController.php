<?php

namespace Loom\Pages\Controllers;

use App\Http\Controllers\Controller;

class PagesController extends Controller
{
    public function index()
    {
        return view('loom-pages::index');
    }
}
