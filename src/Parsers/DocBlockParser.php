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
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ResourceParserGenerator\Converters\DocBlockTypeConverter;
use ResourceParserGenerator\Parsers\Data\DocBlock;
use ResourceParserGenerator\Resolvers\Contracts\ResolverContract;
use ResourceParserGenerator\Types;
use RuntimeException;

class DocBlockParser
{
    public function __construct(
        private readonly DocBlockTypeConverter $typeParser,
        private readonly Lexer $phpDocLexer,
        private readonly PhpDocParser $phpDocParser,
    ) {
        //
    }

    public function parse(string $content, ResolverContract $resolver): DocBlock
    {
        $docBlock = DocBlock::create();

        $content = trim($content);
        if (!$content) {
            return $docBlock;
        }

        $tokens = $this->phpDocLexer->tokenize($content);

        /**
         * @var TokenIterator $tokenIterator
         */
        $tokenIterator = resolve(TokenIterator::class, ['tokens' => $tokens]);

        $docNode = $this->phpDocParser->parse($tokenIterator);

        $this->parseMethods($docNode, $resolver, $docBlock);
        $this->parseParams($docNode, $resolver, $docBlock);
        $this->parseProperties($docNode, $resolver, $docBlock);
        $this->parseReturn($docNode, $resolver, $docBlock);
        $this->parseVars($docNode, $resolver, $docBlock);

        return $docBlock;
    }

    private function parseMethods(PhpDocNode $docNode, ResolverContract $resolver, DocBlock $docBlock): void
    {
        $methodNodes = $docNode->getTagsByName('@method');
        foreach ($methodNodes as $node) {
            if ($node->value instanceof MethodTagValueNode) {
                $docBlock->setMethod(
                    $node->value->methodName,
                    $node->value->returnType
                        ? $this->typeParser->convert($node->value->returnType, $resolver)
                        : new Types\UntypedType(),
                );
            }
        }
    }

    private function parseParams(PhpDocNode $docNode, ResolverContract $resolver, DocBlock $docBlock): void
    {
        $paramNodes = $docNode->getTagsByName('@param');
        foreach ($paramNodes as $node) {
            if ($node->value instanceof ParamTagValueNode) {
                $name = ltrim($node->value->parameterName, '$');
                $docBlock->setParam($name, $this->typeParser->convert($node->value->type, $resolver));
            }
        }
    }

    private function parseProperties(PhpDocNode $docNode, ResolverContract $resolver, DocBlock $docBlock): void
    {
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
                $docBlock->setProperty($name, $this->typeParser->convert($node->value->type, $resolver));
            }
        }
    }

    private function parseReturn(PhpDocNode $docNode, ResolverContract $resolver, DocBlock $docBlock): void
    {
        $returnNodes = $docNode->getTagsByName('@return');
        $returnNodeCount = count($returnNodes);
        if ($returnNodeCount) {
            if ($returnNodeCount > 1) {
                throw new RuntimeException('Multiple @return tags found');
            }

            /**
             * @var PhpDocTagNode $returnNode
             */
            $returnNode = reset($returnNodes);
            if ($returnNode->value instanceof ReturnTagValueNode) {
                $docBlock->setReturn($this->typeParser->convert($returnNode->value->type, $resolver));
            }
        }
    }

    private function parseVars(PhpDocNode $docNode, ResolverContract $resolver, DocBlock $docBlock): void
    {
        $varNodes = $docNode->getTagsByName('@var');
        foreach ($varNodes as $node) {
            if ($node->value instanceof VarTagValueNode) {
                $name = trim($node->value->variableName);
                if ($name) {
                    $name = ltrim($node->value->variableName, '$');
                }

                $docBlock->setVar($name, $this->typeParser->convert($node->value->type, $resolver));
            }
        }
    }
}
