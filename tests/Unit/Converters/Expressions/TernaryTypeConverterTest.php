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
use ResourceParserGenerator\Converters\Data\ConverterContext;
use ResourceParserGenerator\Converters\Expressions\TernaryExprTypeConverter;
use ResourceParserGenerator\Converters\ExpressionTypeConverter;
use ResourceParserGenerator\Tests\TestCase;
use ResourceParserGenerator\Types;

#[CoversClass(TernaryExprTypeConverter::class)]
class TernaryTypeConverterTest extends TestCase
{
    public function testTernaryParserParsesTernaryAndReturnsConvertedSides(): void
    {
        // Arrange
        /**
         * @var ResolverContract|MockInterface $resolver
         */
        $resolver = $this->mock(ResolverContract::class);
        $context = new ConverterContext($resolver);

        /**
         * @var ExpressionTypeConverter|MockInterface $typeConverter
         */
        $typeConverter = $this->mock(ExpressionTypeConverter::class);

        $typeConverter->shouldReceive('convert')
            ->with(Mockery::type(LNumber::class), $context)
            ->andReturn(new Types\IntType());
        $typeConverter->shouldReceive('convert')
            ->with(Mockery::type(DNumber::class), $context)
            ->andReturn(new Types\FloatType());

        $converter = new TernaryExprTypeConverter($typeConverter);

        // Act
        $result = $converter->convert(
            new Ternary(
                new ConstFetch(new Name(['true'])),
                new LNumber(1),
                new DNumber(2.5),
                [],
            ),
            $context,
        );

        // Assert
        $this->assertSame('float|int', $result->describe());
    }
}
