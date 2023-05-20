<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types\Zod;

use ResourceParserGenerator\Contracts\Resolvers\ResourceResolverContract;
use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\DataObjects\Collections\ResourceParserContextCollection;
use RuntimeException;

class ZodShapeReferenceType implements ParserTypeContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserContextCollection|null $localResources
     * @param ResourceResolverContract|null $resourceResolver
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        private readonly ResourceParserContextCollection|null $localResources,
        private readonly ResourceResolverContract|null $resourceResolver,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @param string $methodName
     * @param ResourceParserContextCollection|null $localResources
     * @param ResourceResolverContract|null $resourceResolver
     * @return ZodShapeReferenceType
     */
    public static function create(
        string $className,
        string $methodName,
        ResourceParserContextCollection|null $localResources = null,
        ResourceResolverContract|null $resourceResolver = null,
    ): self {
        return resolve(self::class, [
            'className' => $className,
            'methodName' => $methodName,
            'localResources' => $localResources,
            'resourceResolver' => $resourceResolver,
        ]);
    }

    public function constraint(): string
    {
        return $this->resourceResolver()->resolveVariableName($this->className, $this->methodName);
    }

    public function imports(): array
    {
        if (!$this->localResources()->find($this->className, $this->methodName)) {
            $fileName = $this->resourceResolver()->resolveFileName($this->className, $this->methodName);
            $variableName = $this->resourceResolver()->resolveVariableName(
                $this->className,
                $this->methodName,
            );

            return [
                $fileName => [$variableName],
            ];
        }

        return [];
    }

    private function localResources(): ResourceParserContextCollection
    {
        if (!$this->localResources) {
            throw new RuntimeException(sprintf(
                'ZodShapeReferenceType::availableResources not set for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        return $this->localResources;
    }

    private function resourceResolver(): ResourceResolverContract
    {
        if (!$this->resourceResolver) {
            throw new RuntimeException(sprintf(
                'ZodShapeReferenceType::resourceResolver not set for "%s::%s"',
                $this->className,
                $this->methodName,
            ));
        }

        return $this->resourceResolver;
    }
}
