<?php

namespace Tests\Unit;

use Loom\Builder\Blueprint;
use Loom\Builder\MigrationGenerator;
use PHPUnit\Framework\TestCase;

class MigrationGeneratorTest extends TestCase
{
    public function test_it_generates_drop_migrations_for_removed_fields(): void
    {
        $blueprint = $this->blueprint([
            ['name' => 'title', 'type' => 'text'],
        ]);

        $files = (new MigrationGenerator)->generate(
            $blueprint,
            ['id', 'title', 'legacy_body', 'created_at', 'updated_at'],
            false,
            [
                ['name' => 'title', 'type' => 'text'],
                ['name' => 'legacy_body', 'type' => 'textarea'],
            ],
            [
                'title' => 'string',
                'legacy_body' => 'text',
            ]
        );

        $this->assertCount(1, $files);

        $path = array_key_first($files);
        $this->assertStringContainsString('drop_legacy_body_from_items_table', $path);
    }

    /**
     * @param  list<array{name: string, type: string}>  $fields
     */
    protected function blueprint(array $fields): Blueprint
    {
        return Blueprint::fromArray([
            'is_new' => false,
            'plugin' => [
                'name' => 'items',
                'label' => 'Items',
                'route' => 'items',
            ],
            'model' => [
                'class' => 'Item',
                'table' => 'items',
            ],
            'forms' => [
                [
                    'key' => 'basic-form',
                    'storage' => 'model',
                    'fields' => $fields,
                ],
            ],
        ]);
    }
}
