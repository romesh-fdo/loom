<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Loom\Support\MediaParameterProcessor;
use Loom\Support\ThemeContent\PageBladeDocument;
use Loom\Support\ThemeContent\PageLayoutFieldsComposer;
use Tests\TestCase;

class MediaParameterProcessorLayoutFieldsTest extends TestCase
{
    public function test_it_preserves_dynamic_layout_fields_when_static_value_is_also_posted(): void
    {
        $processor = new MediaParameterProcessor;

        $processed = $processor->processLayoutFields([
            'meta' => [
                'author' => [
                    '_mode' => 'dynamic',
                    'static' => 'Sarab',
                    'import' => 'productDetails',
                    'field' => 'name',
                ],
            ],
        ], Request::create('/', 'POST'), fn () => [
            ['name' => 'author', 'type' => 'text'],
        ]);

        $this->assertSame('dynamic', $processed['meta']['author']['_mode']);
        $this->assertSame('Sarab', $processed['meta']['author']['static']);
        $this->assertSame('productDetails', $processed['meta']['author']['import']);
        $this->assertSame('name', $processed['meta']['author']['field']);
    }

    public function test_it_composes_dynamic_layout_fields_into_page_blade_after_processing(): void
    {
        $processor = new MediaParameterProcessor;

        $processed = $processor->processLayoutFields([
            'meta' => [
                'author' => [
                    '_mode' => 'dynamic',
                    'static' => 'Sarab',
                    'import' => 'productDetails',
                    'field' => 'name',
                ],
            ],
        ], Request::create('/', 'POST'), fn () => [
            ['name' => 'author', 'type' => 'text'],
        ]);

        $layoutFields = [];

        foreach ($processed['meta'] as $fieldName => $fieldValue) {
            if (! is_array($fieldValue) || ! PageLayoutFieldsComposer::isSubmittedDynamicField($fieldValue)) {
                continue;
            }

            $import = trim((string) ($fieldValue['import'] ?? ''));
            $field = trim((string) ($fieldValue['field'] ?? ''));

            if ($import !== '' && $field !== '') {
                $layoutFields['meta'][$fieldName] = PageLayoutFieldsComposer::normalizeDynamicField([
                    'import' => $import,
                    'field' => $field,
                ]);
            }
        }

        $composed = PageBladeDocument::compose(
            ['name' => 'Test Page', 'slug' => 'test-page', 'url' => 'product/{id}', 'layout' => 'custom'],
            [
                [
                    'variable' => 'productDetails',
                    'plugin' => 'loom.asdasd',
                    'function' => 'getFirst',
                    'parameters' => [],
                ],
            ],
            $layoutFields,
            ''
        );

        $this->assertStringContainsString('$productDetails = loom_import(', $composed);
        $this->assertStringContainsString('($productDetails && isset($productDetails->name))', $composed);
        $this->assertStringNotContainsString("'author' => 'Sarab'", $composed);
    }
}
