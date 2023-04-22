<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers\PhpParser;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Parsers\PhpParser\UseStatementParser;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\TestCase;

#[CoversClass(UseStatementParser::class)]
class UseStatementsParserTest extends TestCase
{
    #[DataProvider('expectedUseProvider')]
    public function testGetExpectedUseStatements(string $classFile, array $expectedResult): void
    {
        // Arrange
        // Nothing to arrange.

        // Act
        $typehints = $this->performAction($classFile);

        // Assert
        $this->assertEquals($expectedResult, $typehints);
    }

    public static function expectedUseProvider(): array
    {
        return [
            'normal use' => [
                'classFile' => dirname(__DIR__, 3) . '/Stubs/UserResource.php',
                'expectedResult' => [
                    'User' => User::class,
                ],
            ],
            'aliased use' => [
                'classFile' => dirname(__DIR__, 3) . '/Stubs/Models/User.php',
                'expectedResult' => [
                    'AliasedLaravelModel' => Model::class,
                    'CarbonImmutable' => CarbonImmutable::class,
                ],
            ],
        ];
    }

    /**
     * @param string $classFile
     * @return array<string, string>
     */
    private function performAction(string $classFile): array
    {
        /** @var UseStatementParser $parser */
        $parser = app(UseStatementParser::class);

        return $parser->parse($classFile);
    }
}
