<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Converters\Expressions;

use Mockery;
use Mockery\MockInterface;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Converters\Expressions\TernaryTypeConverter;
use ResourceParserGenerator\Converters\ExprTypeConverter;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Types;

#[CoversClass(TernaryTypeConverter::class)]
class TernaryTypeConverterTest extends TestCase
{
    public function testTernaryParserParsesTernaryAndReturnsConvertedSides(): void
    {
        // Arrange
        /**
         * @var ResolverContract|MockInterface $resolver
         */
        $resolver = $this->mock(ResolverContract::class);

        /**
         * @var ExprTypeConverter|MockInterface $typeConverter
         */
        $typeConverter = $this->mock(ExprTypeConverter::class);

        $typeConverter->shouldReceive('convert')
            ->with(Mockery::type(LNumber::class), $resolver)
            ->andReturn(new Types\IntType());
        $typeConverter->shouldReceive('convert')
            ->with(Mockery::type(DNumber::class), $resolver)
            ->andReturn(new Types\FloatType());

        $converter = new TernaryTypeConverter($typeConverter);

        // Act
        $result = $converter->convert(
            new Ternary(
                new ConstFetch(new Name(['true'])),
                new LNumber(1),
                new DNumber(2.5),
                [],
            ),
            $resolver,
        );

        // Assert
        $this->assertSame('float|int', $result->describe());
    }
}
