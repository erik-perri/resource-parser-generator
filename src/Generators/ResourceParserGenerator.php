<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Str;
use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;

class ResourceParserGenerator
{
    public function generate(ResourceParserContextCollection $parsers): string
    {
        $imports = collect();
        foreach ($parsers->collect() as $parser) {
            foreach ($parser->parserData->properties() as $property) {
                $imports = $imports->mergeRecursive($property->imports());
            }
        }

        $imports = $imports->mergeRecursive(['zod' => ['object', 'output']])
            ->map(fn(array $importItems) => collect($importItems)->unique()->sort()->values()->all())
            ->mapWithKeys(
                fn(array $importItems, string $importName) => [$this->stripExtension($importName) => $importItems],
            )
            ->sort();

        $content = view('resource-parser-generator::resource-parser-file', [
            'imports' => $imports,
            'parsers' => $parsers->collect(),
        ])->render();

        return trim($content) . PHP_EOL;
    }

    private function stripExtension(string $filePath): string
    {
        $info = pathinfo($filePath);

        return isset($info['extension'])
            ? Str::of($filePath)->beforeLast('.' . $info['extension'])->value()
            : $filePath;
    }
}
