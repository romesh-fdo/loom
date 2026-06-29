<?php

namespace Loom\Features\Theme\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Loom\Support\ThemeManager;

class ThemeController extends Controller
{
    public function create(): View
    {
        return view('loom-theme::create');
    }

    public function store(Request $request, ThemeManager $themes): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9-]+$/',
                Rule::notIn([ThemeManager::DEFAULT_SLUG]),
                function (string $attribute, mixed $value, \Closure $fail) use ($themes) {
                    if ($themes->slugExists((string) $value)) {
                        $fail('A theme with this slug already exists.');
                    }
                },
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ]);

        $themes->create(
            $validated['name'],
            $validated['slug'],
            $request->file('image')
        );

        return redirect()
            ->route('admin.settings', ['tab' => 'theme'])
            ->with('success', "Theme \"{$validated['name']}\" created successfully.");
    }

    public function activate(string $slug, ThemeManager $themes): RedirectResponse
    {
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            abort(404);
        }

        $theme = $themes->find($slug);

        if ($theme === null) {
            abort(404);
        }

        $themes->activate($slug);

        return redirect()
            ->route('admin.settings', ['tab' => 'theme'])
            ->with('success', "Theme \"{$theme['name']}\" is now active.");
    }

    public function updateImage(string $slug, Request $request, ThemeManager $themes): RedirectResponse
    {
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            abort(404);
        }

        $theme = $themes->find($slug);

        if ($theme === null) {
            abort(404);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ]);

        $themes->updateImage($slug, $request->file('image'));

        return redirect()
            ->route('admin.settings', ['tab' => 'theme'])
            ->with('success', "Preview image updated for \"{$theme['name']}\".");
    }
}
