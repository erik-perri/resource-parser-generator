<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Filesystem\ClassFileLocatorContract;
use ResourceParserGenerator\Contracts\Parsers\ClassParserContract;
use ResourceParserGenerator\Contracts\Parsers\PhpFileParserContract;
use ResourceParserGenerator\Parsers\Data\ClassScope;
use ResourceParserGenerator\Parsers\Data\ReflectedClassScope;
use ResourceParserGenerator\Types\ClassType;
use RuntimeException;

class ClassParser implements ClassParserContract
{
    private readonly RepositoryContract $cache;

    public function __construct(
        private readonly ClassFileLocatorContract $classLocator,
        private readonly PhpFileParserContract $fileParser,
    ) {
        $this->cache = new Repository(new ArrayStore());
    }

    public function parse(string $className, string|null $staticContext = null): ClassScopeContract
    {
        return $this->cache->rememberForever(
            sprintf('%s::%s', $staticContext, $className),
            fn() => $this->parseClass($className, $staticContext),
        );
    }

    public function parseType(ClassType $type, ?string $staticContext = null,): ClassScopeContract
    {
        $classScope = $this->parse($type->fullyQualifiedName(), $staticContext);
        if (!($classScope instanceof ClassScope)) {
            return $classScope;
        }

        return $type->generics()
            ? ClassScope::create(
                $classScope->fullyQualifiedName(),
                $classScope->node(),
                $classScope->resolver(),
                $classScope->extends(),
                $classScope->traits(),
                $type->generics(),
            )
            : $classScope;
    }

    /**
     * @param class-string $className
     * @param class-string|null $staticContext
     * @return ClassScopeContract
     */
    private function parseClass(string $className, string|null $staticContext = null): ClassScopeContract
    {
        if (class_exists($className) && !$this->classLocator->exists($className)) {
            return ReflectedClassScope::create(new ReflectionClass($className));
        }

        $classFile = $this->classLocator->get($className);

        $contents = File::get($classFile);
        $fileScope = $this->fileParser->parse($contents, $staticContext);

        $firstClass = $fileScope->classes()->first();
        if (!$firstClass) {
            $firstEnum = $fileScope->enums()->first();
            if ($firstEnum) {
                return $firstEnum;
            }

            throw new RuntimeException(sprintf('Could not find class "%s" in file "%s"', $className, $classFile));
        }

        return $firstClass;
    }
}
