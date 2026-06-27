<?php

namespace Loom\Builder;

class GeneratedModelWriter
{
    /**
     * @return array<string, string>
     */
    public function generate(Blueprint $blueprint): array
    {
        $model = $blueprint->modelClass();
        $trait = $model.'Schema';
        $namespace = 'Loom\\'.$blueprint->namespaceStudly().'\\Models\\Generated';

        $fillable = $this->fillableFields($blueprint);
        $casts = $this->casts($blueprint);

        $fillableExport = var_export($fillable, true);
        $castsBody = $this->formatCasts($casts);

        $traitContent = <<<PHP
<?php

namespace {$namespace};

trait {$trait}
{
    public function getTable(): string
    {
        return '{$blueprint->tableName()}';
    }

    public function initialize{$trait}(): void
    {
        \$this->fillable = static::loomFillable();
    }

    /**
     * @return list<string>
     */
    public static function loomFillable(): array
    {
        return {$fillableExport};
    }

    /**
     * @return array<string, string>
     */
    public static function loomCasts(): array
    {
        return {$castsBody};
    }
}

PHP;

        return [
            "models/Generated/{$trait}.php" => $traitContent,
        ];
    }

    public function scaffoldUserModel(Blueprint $blueprint): string
    {
        $model = $blueprint->modelClass();
        $trait = $model.'Schema';
        $namespace = 'Loom\\'.$blueprint->namespaceStudly().'\\Models';

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;
use Loom\\{$blueprint->namespaceStudly()}\\Models\\Generated\\{$trait};

class {$model} extends Model
{
    use {$trait};

    protected function casts(): array
    {
        return static::loomCasts();
    }
}

PHP;
    }

    /**
     * @return list<string>
     */
    protected function fillableFields(Blueprint $blueprint): array
    {
        $fields = array_map(
            fn (array $field) => $field['name'],
            $blueprint->modelFields()
        );

        if ($blueprint->hasConfigFields()) {
            $fields[] = 'config';
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(Blueprint $blueprint): array
    {
        $casts = [];

        foreach ($blueprint->modelFields() as $field) {
            $cast = FieldTypeRegistry::cast($field['type'] ?? 'text');

            if ($cast !== null) {
                $casts[$field['name']] = $cast;
            }
        }

        if ($blueprint->hasConfigFields()) {
            $casts['config'] = 'array';
        }

        return $casts;
    }

    /**
     * @param  array<string, string>  $casts
     */
    protected function formatCasts(array $casts): string
    {
        if ($casts === []) {
            return '[]';
        }

        $lines = [];

        foreach ($casts as $key => $value) {
            $lines[] = "            '{$key}' => '{$value}',";
        }

        return "[\n".implode("\n", $lines)."\n        ]";
    }
}
