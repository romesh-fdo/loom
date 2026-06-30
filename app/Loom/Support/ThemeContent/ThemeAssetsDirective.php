<?php

namespace Loom\Support\ThemeContent;

class ThemeAssetsDirective
{
    public function __construct(
        protected ThemeAssetTagBuilder $tagBuilder = new ThemeAssetTagBuilder(new ThemeAssetUrlResolver),
        protected ThemeAssetEntryGrouper $grouper = new ThemeAssetEntryGrouper,
        protected ThemeAssetCombiner $combiner = new ThemeAssetCombiner,
    ) {}

    public function render(string $template, ThemeRenderContext $context): string
    {
        $offset = 0;
        $length = strlen($template);

        while ($offset < $length) {
            $start = strpos($template, '@assets', $offset);

            if ($start === false) {
                break;
            }

            $openParen = $start + strlen('@assets');
            $openParen = self::skipWhitespace($template, $openParen);

            if ($openParen >= $length || $template[$openParen] !== '(') {
                $offset = $start + 7;

                continue;
            }

            $argEnd = self::findMatchingParen($template, $openParen);

            if ($argEnd === null) {
                $offset = $start + 7;

                continue;
            }

            $literal = substr($template, $openParen + 1, $argEnd - $openParen - 1);
            $replacement = $this->renderLiteral($literal, $context);
            $directiveEnd = $argEnd + 1;

            if ($directiveEnd < $length && $template[$directiveEnd] === ';') {
                $directiveEnd++;
            }

            $template = substr($template, 0, $start).$replacement.substr($template, $directiveEnd);
            $length = strlen($template);
            $offset = $start + strlen($replacement);
        }

        return $template;
    }

    private function renderLiteral(string $literal, ThemeRenderContext $context): string
    {
        $entries = ThemeDirectiveArrayParser::parse(trim($literal));

        if ($entries === null) {
            return '';
        }

        $html = '';

        foreach ($this->grouper->group($entries) as $group) {
            $groupEntries = $group['entries'];

            if ($group['type'] === 'bundle') {
                $html .= $this->renderBundle($groupEntries, (string) $group['asset_type'], $context);

                continue;
            }

            foreach ($groupEntries as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $html .= $this->tagBuilder->build($entry, $context);
            }
        }

        return $html;
    }

    /**
     * @param  list<array<int|string, mixed>>  $entries
     */
    private function renderBundle(array $entries, string $assetType, ThemeRenderContext $context): string
    {
        $paths = [];

        foreach ($entries as $entry) {
            $path = $entry[0] ?? null;

            if (is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }

        if ($paths === []) {
            return '';
        }

        $url = $this->combiner->bundleUrl($context->themeSlug, $assetType, $paths);
        $shorthand = $assetType === 'script' ? 'script' : 'stylesheet';

        return $this->tagBuilder->build([$url, $shorthand], $context);
    }

    private static function findMatchingParen(string $input, int $openPos): ?int
    {
        $depth = 0;
        $length = strlen($input);
        $inString = false;
        $stringQuote = '';

        for ($i = $openPos; $i < $length; $i++) {
            $char = $input[$i];

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $i++;

                    continue;
                }

                if ($char === $stringQuote) {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringQuote = $char;

                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth--;

                if ($depth === 0 && $char === ')') {
                    return $i;
                }
            }
        }

        return null;
    }

    private static function skipWhitespace(string $input, int $pos): int
    {
        $length = strlen($input);

        while ($pos < $length && ctype_space($input[$pos])) {
            $pos++;
        }

        return $pos;
    }
}
