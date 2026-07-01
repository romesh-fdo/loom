<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\PageEntityImportsComposer;
use Tests\TestCase;

class PageEntityImportsComposerTest extends TestCase
{
    public function test_it_composes_and_parses_path_param_declarations(): void
    {
        $imports = [
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getById',
                'parameters' => [
                    'id' => ['mode' => 'path_param', 'param' => 'id'],
                ],
            ],
        ];

        $lines = PageEntityImportsComposer::toPhpLines($imports);
        $php = "@php\n".implode("\n", $lines)."\n@endphp";

        $this->assertStringContainsString("loom_import('loom.asdasd', 'getById'", $php);
        $this->assertStringContainsString("request()->route('id')", $php);

        $parsed = PageEntityImportsComposer::fromPhpBlock($php);

        $this->assertCount(1, $parsed);
        $this->assertSame('productDetails', $parsed[0]['variable']);
        $this->assertSame('path_param', $parsed[0]['parameters']['id']['mode']);
        $this->assertSame('id', $parsed[0]['parameters']['id']['param']);
    }

    public function test_it_composes_and_parses_query_param_declarations(): void
    {
        $imports = [
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getById',
                'parameters' => [
                    'id' => ['mode' => 'query_param', 'param' => 'id'],
                ],
            ],
        ];

        $lines = PageEntityImportsComposer::toPhpLines($imports);
        $php = "@php\n".implode("\n", $lines)."\n@endphp";

        $this->assertStringContainsString("request()->query('id')", $php);

        $parsed = PageEntityImportsComposer::fromPhpBlock($php);

        $this->assertSame('query_param', $parsed[0]['parameters']['id']['mode']);
        $this->assertSame('id', $parsed[0]['parameters']['id']['param']);
    }

    public function test_it_still_parses_legacy_url_segment_declarations(): void
    {
        $php = "@php\n    \$productDetails = loom_import('loom.asdasd', 'getById', ['id' => request()->segment(2)]);\n@endphp";

        $parsed = PageEntityImportsComposer::fromPhpBlock($php);

        $this->assertSame('url_segment', $parsed[0]['parameters']['id']['mode']);
        $this->assertSame(2, $parsed[0]['parameters']['id']['segment']);
    }
}
