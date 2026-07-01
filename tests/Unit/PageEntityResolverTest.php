<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Loom\Asdasd\Models\Asdasd;
use Loom\Support\ThemeContent\PageEntityResolver;
use Loom\System\PluginManager;
use Tests\TestCase;

class PageEntityResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('loom_asdasds', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function test_it_resolves_entity_imports_into_bindings(): void
    {
        $record = Asdasd::query()->create();

        $resolver = new PageEntityResolver(app(PluginManager::class));

        $bindings = $resolver->resolve([
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getById',
                'parameters' => [
                    'id' => ['mode' => 'static', 'value' => (string) $record->id],
                ],
            ],
        ], request());

        $this->assertArrayHasKey('productDetails', $bindings);
        $this->assertSame($record->id, $bindings['productDetails']->id);
    }

    public function test_it_resolves_path_param_bindings_from_route_parameters(): void
    {
        $resolver = new PageEntityResolver(app(PluginManager::class));
        $request = Request::create('/products/42', 'GET');
        $route = new Route('GET', '/{path}', []);
        $route->bind($request);
        $route->setParameter('id', '42');
        $request->setRouteResolver(fn () => $route);

        $bindings = $resolver->resolve([
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getBySlug',
                'parameters' => [
                    'slug' => ['mode' => 'path_param', 'param' => 'id'],
                ],
            ],
        ], $request);

        $this->assertArrayHasKey('productDetails', $bindings);
        $this->assertSame('Custom: 42', $bindings['productDetails']->seo_description);
    }

    public function test_it_resolves_query_param_bindings(): void
    {
        $resolver = new PageEntityResolver(app(PluginManager::class));
        $request = Request::create('/products?id=42', 'GET');

        $bindings = $resolver->resolve([
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getBySlug',
                'parameters' => [
                    'slug' => ['mode' => 'query_param', 'param' => 'id'],
                ],
            ],
        ], $request);

        $this->assertArrayHasKey('productDetails', $bindings);
        $this->assertSame('Custom: 42', $bindings['productDetails']->seo_description);
    }
}
