<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ImportCollectionContract;
use ResourceParserGenerator\Contracts\ParserGeneratorContextContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Import;
use ResourceParserGenerator\DataObjects\ImportCollection;
use RuntimeException;

class ZodShapeReferenceType implements ParserTypeContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
    ) {
        //
    }

    public function constraint(ParserGeneratorContextContract $context): string
    {
        $parserData = $context->find($this->className, $this->methodName);
        if (!$parserData) {
            throw new RuntimeException(sprintf(
                'Unable to find global resource context for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        $outputVariable = $parserData->configuration->variableName;
        if (!$outputVariable) {
            throw new RuntimeException(sprintf(
                'Unable to find output variable name for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        return $outputVariable;
    }

    public function imports(ParserGeneratorContextContract $context): ImportCollectionContract
    {
        // If this resource is available in the local context, then we don't need to import it.
        if ($context->findLocal($this->className, $this->methodName)) {
            return new ImportCollection();
        }

        $parserData = $context->find($this->className, $this->methodName);
        if (!$parserData) {
            throw new RuntimeException(sprintf(
                'Unable to find local resource data for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        $fileName = $parserData->configuration->parserFile;
        $variableName = $parserData->configuration->variableName;
        if (!$fileName || !$variableName) {
            throw new RuntimeException(sprintf(
                'Unable to determine output configuration for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        // TODO Move path part to configuration?
        return new ImportCollection(new Import($variableName, './' . $fileName));
    }
}
