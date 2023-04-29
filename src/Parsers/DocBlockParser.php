<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ResourceParserGenerator\Contracts\ClassNameResolverContract;
use ResourceParserGenerator\Parsers\DataObjects\DocBlock;
use ResourceParserGenerator\Types;
use RuntimeException;

class DocBlockParser
{
    public function __construct(
        private readonly DocBlockTypeParser $typeParser,
    ) {
        //
    }

    public function parse(string $content, ClassNameResolverContract $classResolver): DocBlock
    {
        $docBlock = DocBlock::create();

        $content = trim($content);
        if (!$content) {
            return $docBlock;
        }

        $lexer = new Lexer();
        $constExprParser = new ConstExprParser();
        $phpDocParser = new PhpDocParser(
            new TypeParser($constExprParser),
            $constExprParser,
        );

        $tokens = $lexer->tokenize($content);
        $tokenIterator = new TokenIterator($tokens);

        $docNode = $phpDocParser->parse($tokenIterator);

        $this->parseMethods($docNode, $classResolver, $docBlock);
        $this->parseParams($docNode, $classResolver, $docBlock);
        $this->parseProperties($docNode, $classResolver, $docBlock);
        $this->parseReturn($docNode, $classResolver, $docBlock);
        $this->parseVars($docNode, $classResolver, $docBlock);

        return $docBlock;
    }

    private function parseMethods(
        PhpDocNode $docNode,
        ClassNameResolverContract $classResolver,
        DocBlock $docBlock
    ): void {
        $methodNodes = $docNode->getTagsByName('@method');
        foreach ($methodNodes as $node) {
            if ($node->value instanceof MethodTagValueNode) {
                $docBlock->setMethod(
                    $node->value->methodName,
                    $node->value->returnType
                        ? $this->typeParser->parse($node->value->returnType, $classResolver)
                        : new Types\UntypedType(),
                );
            }
        }
    }

    private function parseParams(
        PhpDocNode $docNode,
        ClassNameResolverContract $classResolver,
        DocBlock $docBlock
    ): void {
        $paramNodes = $docNode->getTagsByName('@param');
        foreach ($paramNodes as $node) {
            if ($node->value instanceof ParamTagValueNode) {
                $name = ltrim($node->value->parameterName, '$');
                $docBlock->setParam($name, $this->typeParser->parse($node->value->type, $classResolver));
            }
        }
    }

    private function parseProperties(
        PhpDocNode $docNode,
        ClassNameResolverContract $classResolver,
        DocBlock $docBlock,
    ): void {
        /**
         * @var Collection<int, PhpDocTagNode> $propertyNodes
         */
        $propertyNodes = collect([
            ...$docNode->getTagsByName('@property'),
            ...$docNode->getTagsByName('@property-read'),
        ]);
        foreach ($propertyNodes as $node) {
            if ($node->value instanceof PropertyTagValueNode) {
                $name = ltrim($node->value->propertyName, '$');
                $docBlock->setProperty($name, $this->typeParser->parse($node->value->type, $classResolver));
            }
        }
    }

    private function parseReturn(
        PhpDocNode $docNode,
        ClassNameResolverContract $classResolver,
        DocBlock $docBlock
    ): void {
        $returnNodes = $docNode->getTagsByName('@return');
        $returnNodeCount = count($returnNodes);
        if ($returnNodeCount) {
            if ($returnNodeCount > 1) {
                throw new RuntimeException('Multiple @return tags found');
            }

            $returnNode = $returnNodes[0];
            if ($returnNode->value instanceof ReturnTagValueNode) {
                $docBlock->setReturn($this->typeParser->parse($returnNode->value->type, $classResolver));
            }
        }
    }

    private function parseVars(PhpDocNode $docNode, ClassNameResolverContract $classResolver, DocBlock $docBlock): void
    {
        $varNodes = $docNode->getTagsByName('@var');
        foreach ($varNodes as $node) {
            if ($node->value instanceof VarTagValueNode) {
                $name = trim($node->value->variableName);
                if ($name) {
                    $name = ltrim($node->value->variableName, '$');
                }

                $docBlock->setVar($name, $this->typeParser->parse($node->value->type, $classResolver));
            }
        }
    }
}
