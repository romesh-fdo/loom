<?php

namespace Loom\Builder;

class PluginRoutesPatcher
{
    public function __construct(
        protected SecureFileWriter $writer,
    ) {}

    public function patch(Blueprint $blueprint): ?string
    {
        $content = $this->writer->read($blueprint->pluginSlug(), 'routes.php');

        if ($content === null) {
            return null;
        }

        $route = $blueprint->routeSlug();

        return preg_replace(
            "/Route::resource\('[^']+'/",
            "Route::resource('{$route}'",
            $content,
            1
        );
    }
}
