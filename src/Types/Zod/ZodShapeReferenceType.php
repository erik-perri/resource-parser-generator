<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\ResourceParserContextRepositoryContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use RuntimeException;

class ZodShapeReferenceType implements ParserTypeContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserContextRepositoryContract $resourceParserRepository
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        private readonly ResourceParserContextRepositoryContract $resourceParserRepository,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ZodShapeReferenceType
     */
    public static function create(
        string $className,
        string $methodName,
    ): self {
        return resolve(self::class, [
            'className' => $className,
            'methodName' => $methodName,
        ]);
    }

    public function constraint(): string
    {
        $context = $this->resourceParserRepository->findGlobal($this->className, $this->methodName);
        if (!$context) {
            throw new RuntimeException(sprintf(
                'Unable to find global resource context for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        $outputVariable = $context->configuration->outputVariable;
        if (!$outputVariable) {
            throw new RuntimeException(sprintf(
                'Unable to find output variable name for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        return $outputVariable;
    }

    public function imports(): array
    {
        if (!$this->resourceParserRepository->findLocal($this->className, $this->methodName)) {
            $context = $this->resourceParserRepository->findGlobal($this->className, $this->methodName);
            if (!$context) {
                throw new RuntimeException(sprintf(
                    'Unable to find local resource context for "%s::%s"',
                    $this->className,
                    $this->methodName,
                ));
            }

            $fileName = $context->configuration->outputFilePath;
            if (!$fileName) {
                throw new RuntimeException(sprintf(
                    'Unable to find output file path for "%s::%s"',
                    $this->className,
                    $this->methodName,
                ));
            }

            $variableName = $context->configuration->outputVariable;
            if (!$variableName) {
                throw new RuntimeException(sprintf(
                    'Unable to find output variable name for "%s::%s"',
                    $this->className,
                    $this->methodName,
                ));
            }

            return [
                // TODO Move path part to configuration?
                './' . $fileName => [$variableName],
            ];
        }

        return [];
    }
}
