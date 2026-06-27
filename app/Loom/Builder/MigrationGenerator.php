<?php

namespace Loom\Builder;

class MigrationGenerator
{
    /**
     * @param  list<string>  $existingColumns
     * @param  list<array<string, mixed>>  $previousFields
     * @param  array<string, string>  $columnSqlTypes
     * @return array<string, string> relative path => PHP content
     */
    public function generate(
        Blueprint $blueprint,
        array $existingColumns = [],
        bool $isCreate = false,
        array $previousFields = [],
        array $columnSqlTypes = [],
    ): array {
        $files = [];
        $table = $blueprint->tableName();
        $timestamp = now()->format('Y_m_d_His');

        if ($isCreate) {
            $files["updates/{$timestamp}_create_{$table}_table.php"] = $this->createTableMigration($blueprint);

            return $files;
        }

        $currentFields = $blueprint->modelFields();
        $currentNames = array_map(fn (array $field) => $field['name'], $currentFields);
        $previousByName = collect($previousFields)->keyBy('name');

        foreach ($previousFields as $previousField) {
            $name = $previousField['name'];

            if (in_array($name, $currentNames, true)) {
                continue;
            }

            if (! in_array($name, $existingColumns, true)) {
                continue;
            }

            $files["updates/{$timestamp}_drop_{$name}_from_{$table}_table.php"] = $this->dropColumnMigration(
                $table,
                $name,
                $previousField
            );
            $timestamp = now()->addSecond()->format('Y_m_d_His');
        }

        foreach ($currentFields as $field) {
            $name = $field['name'];

            if (! in_array($name, $existingColumns, true)) {
                continue;
            }

            $expectedType = FieldTypeRegistry::sqlType($field['type'] ?? 'text');
            $currentType = $columnSqlTypes[$name] ?? null;

            if ($currentType === null || $expectedType === $currentType) {
                continue;
            }

            $previousField = $previousByName->get($name, $this->fieldFromSqlType($name, $currentType));

            $files["updates/{$timestamp}_change_{$name}_on_{$table}_table.php"] = $this->changeColumnMigration(
                $table,
                $field,
                $previousField
            );
            $timestamp = now()->addSecond()->format('Y_m_d_His');
        }

        $needsConfig = $blueprint->hasConfigFields() && ! in_array('config', $existingColumns, true);

        if ($needsConfig) {
            $files["updates/{$timestamp}_add_config_to_{$table}_table.php"] = $this->addConfigColumnMigration($table);
            $timestamp = now()->addSecond()->format('Y_m_d_His');
        }

        foreach ($currentFields as $field) {
            $column = $field['name'];

            if (! in_array($column, $existingColumns, true)) {
                $files["updates/{$timestamp}_add_{$column}_to_{$table}_table.php"] = $this->addColumnMigration($table, $field);
                $timestamp = now()->addSecond()->format('Y_m_d_His');
            }
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
     * @param  array<string, mixed>  $previousField
     */
    protected function dropColumnMigration(string $table, string $column, array $previousField): string
    {
        $definition = $this->columnDefinition($previousField);

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
            \$table->dropColumn('{$column}');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            {$definition}
        });
    }
};

PHP;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $previousField
     */
    protected function changeColumnMigration(string $table, array $field, array $previousField): string
    {
        $definition = $this->columnChangeDefinition($field);
        $previousDefinition = $this->columnChangeDefinition($previousField);

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
            {$previousDefinition}
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

    /**
     * @param  array<string, mixed>  $field
     */
    protected function columnChangeDefinition(array $field): string
    {
        return rtrim($this->columnDefinition($field), ';').'->change();';
    }

    /**
     * @return array{name: string, type: string}
     */
    protected function fieldFromSqlType(string $name, string $sqlType): array
    {
        $type = match ($sqlType) {
            'text' => 'textarea',
            'integer' => 'number',
            'boolean' => 'checkbox',
            'json' => 'dynamic_code',
            default => 'text',
        };

        return [
            'name' => $name,
            'type' => $type,
        ];
    }
}
