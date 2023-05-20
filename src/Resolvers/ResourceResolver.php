<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Resolvers;

use ResourceParserGenerator\Contracts\Resolvers\ResourceResolverContract;
use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;
use ResourceParserGenerator\DataObjects\ResourceContext;
use RuntimeException;

class ResourceResolver implements ResourceResolverContract
{
    public function __construct(private readonly ResourceParserContextCollection $parsers)
    {
        //
    }

    public static function create(ResourceParserContextCollection $parsers): self
    {
        return resolve(self::class, ['parsers' => $parsers]);
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return string
     */
    public function resolveFileName(string $className, string $methodName): string
    {
        $context = $this->getParserContext($className, $methodName);
        if (!$context->configuration->outputFilePath) {
            throw new RuntimeException(
                sprintf('Could not resolve output path for "%s::%s"', $className, $methodName),
            );
        }

        return './' . $context->configuration->outputFilePath;
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return string
     */
    public function resolveVariableName(string $className, string $methodName): string
    {
        $context = $this->getParserContext($className, $methodName);
        if (!$context->configuration->outputVariable) {
            throw new RuntimeException(
                sprintf('Could not resolve output variable for "%s::%s"', $className, $methodName),
            );
        }

        return $context->configuration->outputVariable;
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ResourceContext
     */
    private function getParserContext(string $className, string $methodName): ResourceContext
    {
        $config = $this->parsers->find($className, $methodName);
        if (!$config) {
            throw new RuntimeException(sprintf('Could not find configuration for "%s::%s"', $className, $methodName));
        }

        return $config;
    }
}
