<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Loom\Asdasd\Models\Asdasd;
use Loom\System\PluginManager;
use Loom\System\PluginModelFunctions;
use Tests\TestCase;

class PluginManagerFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('loom_asdasds', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function test_it_builds_default_model_functions_for_plugins_with_models(): void
    {
        $catalog = app(PluginManager::class)->getFunctionsCatalog();

        $asdasd = collect($catalog)->firstWhere('identifier', 'loom.asdasd');

        $this->assertNotNull($asdasd);
        $this->assertArrayHasKey('getById', $asdasd['functions']);
        $this->assertArrayHasKey('getFirst', $asdasd['functions']);
        $this->assertTrue($asdasd['functions']['getById']['builtin'] ?? false);
    }

    public function test_it_calls_default_get_by_id_function(): void
    {
        $record = Asdasd::query()->create();

        $result = app(PluginManager::class)->callFunction('loom.asdasd', 'getById', [
            'id' => $record->id,
        ]);

        $this->assertNotNull($result);
        $this->assertSame($record->id, $result->id);
    }

    public function test_it_exposes_model_return_fields_in_function_definitions(): void
    {
        $plugin = app(PluginManager::class)->getPlugin('loom.asdasd');

        $this->assertNotNull($plugin);

        $definitions = PluginModelFunctions::definitionsFor($plugin);
        $returnNames = collect($definitions['getById']['returns'])->pluck('name')->all();

        $this->assertContains('id', $returnNames);
        $this->assertContains('created_at', $returnNames);
    }

    public function test_it_merges_custom_register_functions_with_model_defaults(): void
    {
        $catalog = app(PluginManager::class)->getFunctionsCatalog();
        $asdasd = collect($catalog)->firstWhere('identifier', 'loom.asdasd');

        $this->assertNotNull($asdasd);
        $this->assertArrayHasKey('getById', $asdasd['functions']);
        $this->assertArrayHasKey('getBySlug', $asdasd['functions']);
        $this->assertFalse($asdasd['functions']['getBySlug']['builtin'] ?? true);
    }

    public function test_it_calls_custom_register_functions(): void
    {
        $result = app(PluginManager::class)->callFunction('loom.asdasd', 'getBySlug', [
            'slug' => 'demo-item',
        ]);

        $this->assertNotNull($result);
        $this->assertSame('Demo item', $result->name);
        $this->assertSame('Custom: demo-item', $result->seo_description);
    }
}
