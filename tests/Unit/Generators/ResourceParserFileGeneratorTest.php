<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Generators;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Filesystem\SplitByResourceFileSplitter;
use ResourceParserGenerator\Generators\ResourceParserFileGenerator;
use ResourceParserGenerator\Parsers\ResourceParser;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ResourceParserFileGenerator::class)]
class ResourceParserFileGeneratorTest extends TestCase
{
    #[DataProvider('expectedContentProvider')]
    public function testOutputsExpectedContent(string $className, string $methodName, Collection $expectedFiles): void
    {
        // Arrange
        $parsers = $this->make(ResourceParser::class)->parse($className, $methodName);

        // Act
        $generated = $this->make(ResourceParserFileGenerator::class)->generate(
            $parsers,
            $this->make(SplitByResourceFileSplitter::class),
        );

        // Assert
        $this->assertCount($expectedFiles->count(), $generated);
        $expectedFiles->each(function (string $expectedContent, string $fileName) use ($generated) {
            $this->assertTrue($generated->has($fileName));
            $this->assertEquals($expectedContent, $generated->get($fileName));
        });
    }

    public static function expectedContentProvider(): array
    {
        return [
            'UserResource::relatedResource' => [
                'className' => UserResource::class,
                'methodName' => 'relatedResource',
                'expectedFiles' => collect([
                    'userResourceParsers.ts' => file_get_contents(
                        dirname(__DIR__, 2) . '/Examples/Output/Split/userResourceParsers.ts.txt',
                    ),
                    'relatedResourceParsers.ts' => file_get_contents(
                        dirname(__DIR__, 2) . '/Examples/Output/Split/relatedResourceParsers.ts.txt',
                    ),
                ]),
            ],
        ];
    }
}
