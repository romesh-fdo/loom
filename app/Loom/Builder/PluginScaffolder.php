<?php

namespace Loom\Builder;

use Illuminate\Support\Str;

class PluginScaffolder
{
    /**
     * @return array<string, string>
     */
    public function scaffoldFiles(Blueprint $blueprint): array
    {
        $ns = $blueprint->namespaceStudly();
        $model = $blueprint->modelClass();
        $slug = $blueprint->pluginSlug();
        $route = $blueprint->routeSlug();
        $label = $blueprint->pluginLabel();
        $icon = $blueprint->pluginIcon();
        $viewNs = $blueprint->viewNamespace();
        $routeKey = Str::singular(str_replace('-', '_', $route));
        $controller = $model.'sController';
        if (Str::endsWith($model, 's')) {
            $controller = $model.'Controller';
        } elseif (! Str::endsWith($model, 's')) {
            $controller = Str::plural($model).'Controller';
        }

        $hasConfig = $blueprint->hasConfigFields() || file_exists($blueprint->pluginPath().'/schemas/configuration.json');
        $listField = $blueprint->modelFields()[0]['name'] ?? 'title';
        $registerForms = $hasConfig
            ? "'basic-form' => ['schema' => 'basic'],\n            'configuration-form' => ['schema' => 'configuration'],"
            : "'basic-form' => ['schema' => 'basic'],";

        $files = [
            'plugin.yaml' => app(PluginManifestGenerator::class)->generate($blueprint),
            'Plugin.php' => $this->renderStub('Plugin.php.stub', [
                'namespace' => $ns,
                'label' => $label,
                'route' => $route,
                'icon' => $icon,
                'registerForms' => $registerForms,
            ]),
            'routes.php' => $this->renderStub('routes.php.stub', [
                'controllerFqn' => "Loom\\{$ns}\\Controllers\\{$controller}",
                'route' => $route,
            ]),
            'config.php' => $this->renderStub('config.php.stub', [
                'listField' => $listField,
            ]),
            "controllers/{$controller}.php" => $this->renderStub('Controller.php.stub', [
                'namespace' => $ns,
                'controller' => $controller,
                'model' => $model,
                'viewNamespace' => $viewNs,
                'routeKey' => $routeKey,
                'route' => $route,
                'pluginSlug' => $slug,
                'label' => Str::singular($label),
                'listField' => $listField,
            ]),
            'views/index.blade.php' => $this->renderStub('views/index.blade.php.stub', [
                'viewNamespace' => $viewNs,
                'label' => $label,
                'route' => $route,
                'routeKey' => $routeKey,
                'listField' => $listField,
            ]),
            'views/create.blade.php' => $this->renderStub('views/create.blade.php.stub', [
                'viewNamespace' => $viewNs,
                'label' => Str::singular($label),
            ]),
            'views/edit.blade.php' => $this->renderStub('views/edit.blade.php.stub', [
                'viewNamespace' => $viewNs,
                'label' => Str::singular($label),
                'routeKey' => $routeKey,
            ]),
            'views/_form.blade.php' => $this->renderStub('views/_form.blade.php.stub', [
                'viewNamespace' => $viewNs,
                'route' => $route,
                'routeKey' => $routeKey,
                'hasConfig' => $hasConfig ? 'true' : 'false',
            ]),
            'views/_panel-header.blade.php' => $this->renderStub('views/_panel-header.blade.php.stub', [
                'hasConfig' => $hasConfig ? 'true' : 'false',
            ]),
        ];

        return $files;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $name, array $replacements): string
    {
        $path = __DIR__.'/stubs/'.$name;

        if (! file_exists($path)) {
            throw new \RuntimeException("Stub not found: {$name}");
        }

        $content = file_get_contents($path);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{ '.$key.' }}', $value, $content);
        }

        return $content;
    }
}
