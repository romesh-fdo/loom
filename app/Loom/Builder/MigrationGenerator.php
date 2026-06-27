<?php

namespace Loom\Builder;

class MigrationGenerator
{
    /**
     * @param  list<string>  $existingColumns
     * @return array<string, string> relative path => PHP content
     */
    public function generate(Blueprint $blueprint, array $existingColumns = [], bool $isCreate = false): array
    {
        $files = [];
        $table = $blueprint->tableName();
        $timestamp = now()->format('Y_m_d_His');

        if ($isCreate) {
            $files["updates/{$timestamp}_create_{$table}_table.php"] = $this->createTableMigration($blueprint);

            return $files;
        }

        $newColumns = [];

        foreach ($blueprint->modelFields() as $field) {
            $name = $field['name'];

            if (! in_array($name, $existingColumns, true)) {
                $newColumns[] = $field;
            }
        }

        $needsConfig = $blueprint->hasConfigFields() && ! in_array('config', $existingColumns, true);

        if ($needsConfig) {
            $files["updates/{$timestamp}_add_config_to_{$table}_table.php"] = $this->addConfigColumnMigration($table);
            $timestamp = now()->addSecond()->format('Y_m_d_His');
        }

        foreach ($newColumns as $field) {
            $column = $field['name'];
            $files["updates/{$timestamp}_add_{$column}_to_{$table}_table.php"] = $this->addColumnMigration($table, $field);
            $timestamp = now()->addSecond()->format('Y_m_d_His');
        }

        return $files;
    }

    protected function createTableMigration(Blueprint $blueprint): string
    {
        $table = $blueprint->tableName();
        $columns = [];

        foreach ($blueprint->modelFields() as $field) {
            $columns[] = $this->columnDefinition($field);
        }

        if ($blueprint->hasConfigFields()) {
            $columns[] = "\$table->json('config')->nullable();";
        }

        $columnLines = implode("\n            ", $columns);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            {$columnLines}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    }

    protected function addConfigColumnMigration(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->json('config')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn('config');
        });
    }
};

PHP;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function addColumnMigration(string $table, array $field): string
    {
        $column = $field['name'];
        $definition = $this->columnDefinition($field);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            {$definition}
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn('{$column}');
        });
    }
};

PHP;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function columnDefinition(array $field): string
    {
        $name = $field['name'];
        $type = $field['type'] ?? 'text';
        $sql = FieldTypeRegistry::sqlType($type);

        return match ($sql) {
            'text' => "\$table->text('{$name}')->nullable();",
            'json' => "\$table->json('{$name}');",
            'integer' => "\$table->integer('{$name}')->nullable();",
            'boolean' => "\$table->boolean('{$name}')->default(false);",
            default => "\$table->string('{$name}')->nullable();",
        };
    }
}
