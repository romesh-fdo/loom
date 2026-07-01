<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Loom\Http\Controllers\Concerns\ResolvesActiveTheme;
use Loom\Support\ThemeContent\LayoutStore;
use Loom\Support\ThemeContent\PageEntityResolver;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeContent\ThemeLayoutRenderer;
use Loom\Support\ThemeContent\ThemePageRenderer;
use Loom\Support\ThemeManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    use ResolvesActiveTheme;

    public function __construct(
        protected PageStore $pages,
        protected LayoutStore $layouts,
        protected ThemePageRenderer $renderer,
        protected ThemeLayoutRenderer $layoutRenderer,
        protected ThemeManager $themes,
        protected PageEntityResolver $entityResolver,
    ) {}

    public function show(?string $path = null): Response
    {
        $path = is_string($path) ? $path : '';
        $themeSlug = $this->activeThemeSlug();
        $match = $this->pages->matchPath($path, $themeSlug);

        if ($match === null) {
            throw new NotFoundHttpException;
        }

        $page = $match->page;
        $route = request()->route();

        if ($route !== null) {
            foreach ($match->params as $name => $value) {
                $route->setParameter($name, $value);
            }
        }

        $layoutSlug = (string) ($page->layout ?? '');
        $layout = $this->layouts->find($layoutSlug, $themeSlug);

        if ($layout === null) {
            throw new NotFoundHttpException;
        }

        $content = $this->renderer->render($page, $themeSlug);
        $entityImports = is_array($page->entity_imports ?? null) ? $page->entity_imports : [];
        $bindings = $this->entityResolver->resolve($entityImports, request());
        $html = $this->layoutRenderer->render($layout, $content, $themeSlug, $page, $bindings);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
