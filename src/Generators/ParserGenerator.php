<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\Generators\ParserGeneratorContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use ResourceParserGenerator\DataObjects\ParserData;

class ParserGenerator implements ParserGeneratorContract
{
    /**
     * @param ParserData $parser
     * @param Collection<int, ParserData> $otherParsers
     * @return string
     */
    public function generate(ParserData $parser, Collection $otherParsers): string
    {
        // TODO Make these imports configurable.
        $imports = new ImportCollection(
            new Import('object', 'zod'),
            new Import('output', 'zod'),
        );

        $generatorContext = resolve(ParserGeneratorContextContract::class, [
            'parsers' => $otherParsers,
        ]);

        foreach ($parser->properties as $property) {
            $imports = $imports->merge($property->imports($generatorContext));
        }

        $content = [];

        foreach ($imports->groupForView() as $module) {
            $line = [];
            if ($module->defaultImport()) {
                $line[] = $module->defaultImport();
            }
            if (count($module->imports())) {
                $line[] = '{' . implode(', ', $module->imports()) . '}';
            }
            $content[] = sprintf("import %s from '%s';", implode(', ', $line), $module->module());
        }

        $content[] = '';
        $content[] = sprintf('export const %s = object({', $parser->configuration->variableName);

        foreach ($parser->properties as $name => $type) {
            if ($type instanceof ParserTypeWithCommentContract && $type->comment()) {
                $content[] = '  /**';
                $content[] = sprintf('   * %s', $type->comment());
                $content[] = '   */';
            }
            $content[] = sprintf('  %s: %s,', $name, $type->constraint($generatorContext));
        }

        $content[] = '});';

        $content[] = '';
        $content[] = sprintf(
            'export type %s = output<typeof %s>;',
            $parser->configuration->typeName,
            $parser->configuration->variableName
        );

        return trim(implode("\n", $content)) . "\n";
    }
}
