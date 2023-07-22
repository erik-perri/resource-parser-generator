<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;

class ZodNativeEnum implements ParserTypeContract
{
    public function __construct(
        public readonly string $importName,
        public readonly string $importFile,
        public readonly bool $isDefaultImport,
    ) {
        //
    }

    public function constraint(ParserGeneratorContextContract $context): string
    {
        return sprintf('nativeEnum(%s)', $this->importName);
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        return new ImportCollection(
            new Import('nativeEnum', 'zod'),
            new Import($this->importName, $this->importFile, $this->isDefaultImport),
        );
    }
}
