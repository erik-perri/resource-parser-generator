<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Filesystem;

use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Filesystem\ImportPathFinder;
use ResourceParserGenerator\Tests\TestCase;

class ImportPathFinderTest extends TestCase
{
    /** @dataProvider expectedResultProvider */
    #[DataProvider('expectedResultProvider')]
    public function testReturnsExpectedResults(
        string $importingFrom,
        string $importingTo,
        ?string $expectedResult,
    ): void {
        // Arrange
        /** @var ImportPathFinder $finder */
        $finder = $this->app->make(ImportPathFinder::class);

        // Act
        $result = $finder->find($importingFrom, $importingTo);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    public static function expectedResultProvider(): array
    {
        return [
            'adjacent path' => [
                'importingFrom' => './generated/enums',
                'importingTo' => './generated/parsers',
                'expectedResult' => '../enums',
            ],
            'adjacent absolute path' => [
                'importingFrom' => '/code/resources/generated/enums',
                'importingTo' => '/code/resources/generated/parsers',
                'expectedResult' => '../enums',
            ],
            'child path' => [
                'importingFrom' => './generated/enums',
                'importingTo' => './generated',
                'expectedResult' => './enums',
            ],
            'deep adjacent path' => [
                'importingFrom' => './generated/as/deep/as/this/would/be/weird/enums',
                'importingTo' => './generated/parsers',
                'expectedResult' => '../as/deep/as/this/would/be/weird/enums',
            ],
            'non-adjacent absolute path' => [
                'importingFrom' => '/www/resources/generated/enums',
                'importingTo' => '/code/resources/generated/parsers',
                'expectedResult' => '../../../../www/resources/generated/enums',
            ],
            'non-adjacent relative path' => [
                'importingFrom' => './a/resources/generated/enums',
                'importingTo' => './b/resources/generated/parsers',
                'expectedResult' => '../../../../a/resources/generated/enums',
            ],
            'non-adjacent path' => [
                'importingFrom' => 'a/resources/generated/enums',
                'importingTo' => 'b/resources/generated/parsers',
                'expectedResult' => null,
            ],
            'parent path' => [
                'importingFrom' => './generated',
                'importingTo' => './generated/parsers',
                'expectedResult' => '..',
            ],
            'same path' => [
                'importingFrom' => './generated',
                'importingTo' => './generated',
                'expectedResult' => '.',
            ],
            'shallow adjacent path' => [
                'importingFrom' => './generated/enums',
                'importingTo' => './generated/as/deep/as/this/would/be/weird/parsers',
                'expectedResult' => '../../../../../../../../enums',
            ],
        ];
    }
}
