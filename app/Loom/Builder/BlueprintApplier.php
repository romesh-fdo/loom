<?php

namespace Loom\Builder;

class BlueprintApplier
{
    public function __construct(
        protected BlueprintSynchronizer $synchronizer,
    ) {}

    /**
     * @return array{success: bool, message: string, migrate_output?: string}
     */
    public function apply(Blueprint $blueprint): array
    {
        $result = $this->synchronizer->sync($blueprint);

        if ($result['migration_failed']) {
            return [
                'success' => false,
                'message' => 'Migration may have failed. Review output.',
                'migrate_output' => $result['migrate_output'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Plugin saved successfully.',
            'migrate_output' => $result['migrate_output'],
        ];
    }
}
