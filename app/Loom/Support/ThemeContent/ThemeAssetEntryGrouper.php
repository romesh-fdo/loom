<?php

namespace Loom\Support\ThemeContent;

class ThemeAssetEntryGrouper
{
    /**
     * @param  array<int|string, mixed>  $entries
     * @return list<array{type: string, asset_type?: string, entries: list<array<int|string, mixed>>}>
     */
    public function group(array $entries): array
    {
        $groups = [];
        $currentBundle = null;

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if ($this->canBundleEntry($entry)) {
                $assetType = $this->assetTypeForEntry($entry);
                $path = (string) ($entry[0] ?? '');

                if (
                    $currentBundle !== null
                    && ($currentBundle['asset_type'] ?? null) === $assetType
                ) {
                    $currentBundle['entries'][] = $entry;

                    continue;
                }

                if ($currentBundle !== null) {
                    $groups[] = $this->finalizeBundle($currentBundle);
                }

                $currentBundle = [
                    'asset_type' => $assetType,
                    'entries' => [$entry],
                ];

                continue;
            }

            if ($currentBundle !== null) {
                $groups[] = $this->finalizeBundle($currentBundle);
                $currentBundle = null;
            }

            $groups[] = [
                'type' => 'single',
                'entries' => [$entry],
            ];
        }

        if ($currentBundle !== null) {
            $groups[] = $this->finalizeBundle($currentBundle);
        }

        return $groups;
    }

    /**
     * @param  array<int|string, mixed>  $entry
     */
    private function canBundleEntry(array $entry): bool
    {
        if (! (bool) config('loom.assets.bundle', true)) {
            return false;
        }

        $path = $entry[0] ?? null;
        $second = $entry[1] ?? null;

        if (! is_string($path) || $path === '') {
            return false;
        }

        if (! ThemeAssetCombiner::isBundleablePath($path)) {
            return false;
        }

        if (! is_string($second) || ! ThemeAssetCombiner::isBundlableShorthand($second)) {
            return false;
        }

        return $this->extraAttributes($entry) === [];
    }

    /**
     * @param  array<int|string, mixed>  $entry
     */
    private function assetTypeForEntry(array $entry): string
    {
        return ($entry[1] ?? '') === 'script' ? 'script' : 'stylesheet';
    }

    /**
     * @param  array{asset_type: string, entries: list<array<int|string, mixed>>}  $bundle
     * @return array{type: string, asset_type: string, entries: list<array<int|string, mixed>>}
     */
    private function finalizeBundle(array $bundle): array
    {
        $entries = $bundle['entries'];

        return [
            'type' => count($entries) > 1 ? 'bundle' : 'single',
            'asset_type' => $bundle['asset_type'],
            'entries' => $entries,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $entry
     * @return array<string, string>
     */
    private function extraAttributes(array $entry): array
    {
        $attrs = [];

        foreach ($entry as $key => $value) {
            if (! is_string($key) || is_numeric($key)) {
                continue;
            }

            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                $attrs[$key] = (string) $value;
            }
        }

        return $attrs;
    }
}
