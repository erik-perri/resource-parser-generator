<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Builders;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Builders\ResourceParserBuilder;
use ResourceParserGenerator\Parsers\ResourceReturnParser;
use ResourceParserGenerator\Tests\Examples\UserResource;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(ResourceParserBuilder::class)]
class ResourceParserBuilderTest extends TestCase
{
    #[DataProvider('expectedContentProvider')]
    public function testOutputsExpectedContent(string $className, string $methodName, string $expectedContent): void
    {
        // Arrange
        $parsers = $this->make(ResourceReturnParser::class)->parse($className, $methodName);

        // Act
        $contents = $this->make(ResourceParserBuilder::class)->build($parsers);

        // Assert
        $this->assertEquals($expectedContent, $contents);
    }

    public static function expectedContentProvider(): array
    {
        return [
            'UserResource::adminList' => [
                'className' => UserResource::class,
                'methodName' => 'adminList',
                'expectedContent' => file_get_contents(
                    dirname(__DIR__, 2) . '/Examples/Output/userResourceAdminListParser.ts.txt',
                ),
            ],
            'UserResource::combined' => [
                'className' => UserResource::class,
                'methodName' => 'combined',
                'expectedContent' => file_get_contents(
                    dirname(__DIR__, 2) . '/Examples/Output/userResourceCombinedParser.ts.txt',
                ),
            ],
            'UserResource::relatedResource' => [
                'className' => UserResource::class,
                'methodName' => 'relatedResource',
                'expectedContent' => file_get_contents(
                    dirname(__DIR__, 2) . '/Examples/Output/userResourceRelatedResourceParser.ts.txt',
                ),
            ],
        ];
    }
}
