<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Resolvers;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\DataObjects\FileScope;
use ResourceParserGenerator\Resolvers\ClassNameResolver;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ClassNameResolver::class)]
class ClassNameResolverTest extends TestCase
{
    #[DataProvider('classProvider')]
    public function testResolvesClasses(
        Closure $fileScopeFactory,
        string $inputClass,
        bool $isRelative,
        string|null $expected
    ): void {
        // Arrange
        $fileScope = $fileScopeFactory->call($this);

        $resolver = $this->make(ClassNameResolver::class, [
            'fileScope' => $fileScope,
        ]);

        // Act
        $result = $resolver->resolve($inputClass, $isRelative);

        // Assert
        $this->assertSame($expected, $result);
    }

    public static function classProvider(): array
    {
        return [
            'unknown class' => [
                'fileScopeFactory' => fn() => FileScope::create(),
                'className' => 'What',
                'isRelative' => false,
                'expected' => null,
            ],
            'unknown relative class' => [
                'fileScopeFactory' => fn() => FileScope::create(),
                'className' => 'What\Is\This',
                'isRelative' => true,
                'expected' => null,
            ],
            'fully qualified' => [
                'fileScopeFactory' => fn() => FileScope::create()
                    ->addImport('Collection', 'Illuminate\Support\Collection'),
                'className' => 'Illuminate\Support\Collection',
                'isRelative' => false,
                'expected' => 'Illuminate\Support\Collection',
            ],
            'unqualified' => [
                'fileScopeFactory' => fn() => FileScope::create()
                    ->addImport('Collection', 'Illuminate\Support\Collection'),
                'className' => 'Collection',
                'isRelative' => false,
                'expected' => 'Illuminate\Support\Collection',
            ],
            'relative to alias' => [
                'fileScopeFactory' => fn() => FileScope::create()
                    ->addImport('ParserTypes', 'ResourceParserGenerator\Parsers\Types'),
                'className' => 'ParserTypes\ClassType',
                'isRelative' => true,
                'expected' => 'ResourceParserGenerator\Parsers\Types\ClassType',
            ],
        ];
    }
}
