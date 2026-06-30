<?php

namespace Loom\Features\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        session(['loom.file_manager.disk' => 'media']);

        return view('loom-media::index');
    }

    public function preparePicker(): JsonResponse
    {
        session(['loom.file_manager.disk' => 'media']);

        return response()->json(['status' => 'ok']);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];
        $path = $file->store('', 'media');

        return response()->json([
            'url' => Storage::disk('media')->url($path),
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
