<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use ResourceParserGenerator\Actions\GetUseStatementsFromFileAction;
use ResourceParserGenerator\Tests\Stubs\Models\User;
use ResourceParserGenerator\Tests\TestCase;

/**
 * @covers GetUseStatementsFromFileAction
 */
class GetUseStatementsFromFileActionTest extends TestCase
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
                'classFile' => dirname(__DIR__, 2) . '/Stubs/UserResource.php',
                'expectedResult' => [
                    'User' => User::class,
                ],
            ],
            'aliased use' => [
                'classFile' => dirname(__DIR__, 2) . '/Stubs/Models/User.php',
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
        /** @var GetUseStatementsFromFileAction $action */
        $action = app(GetUseStatementsFromFileAction::class);

        return $action->execute($classFile);
    }
}
