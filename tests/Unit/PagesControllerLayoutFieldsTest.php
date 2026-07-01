<?php

namespace Tests\Unit;

use Loom\Features\Pages\Controllers\PagesController;
use ReflectionMethod;
use Tests\TestCase;

class PagesControllerLayoutFieldsTest extends TestCase
{
    public function test_it_normalizes_static_layout_fields_when_dynamic_inputs_are_empty(): void
    {
        $controller = app(PagesController::class);
        $method = new ReflectionMethod(PagesController::class, 'normalizeLayoutFields');
        $method->setAccessible(true);

        $normalized = $method->invoke($controller, [
            'meta' => [
                'author' => [
                    '_mode' => 'static',
                    'static' => 'Jane Doe',
                    'import' => '',
                    'field' => '',
                ],
                'description' => [
                    '_mode' => 'static',
                    'static' => 'A great page',
                    'import' => '',
                    'field' => '',
                ],
            ],
        ], 'custom', []);

        $this->assertSame('Jane Doe', $normalized['meta']['author']);
        $this->assertSame('A great page', $normalized['meta']['description']);
    }

    public function test_it_normalizes_dynamic_layout_fields_when_mode_is_dynamic(): void
    {
        $controller = app(PagesController::class);
        $method = new ReflectionMethod(PagesController::class, 'normalizeLayoutFields');
        $method->setAccessible(true);

        $normalized = $method->invoke($controller, [
            'meta' => [
                'author' => [
                    '_mode' => 'dynamic',
                    'static' => 'Ignored',
                    'import' => 'productDetails',
                    'field' => 'name',
                ],
            ],
        ], 'custom', [
            ['variable' => 'productDetails', 'plugin' => 'loom.asdasd', 'function' => 'getFirst', 'parameters' => []],
        ]);

        $this->assertSame('productDetails', $normalized['meta']['author']['import']);
        $this->assertSame('name', $normalized['meta']['author']['field']);
    }
}
