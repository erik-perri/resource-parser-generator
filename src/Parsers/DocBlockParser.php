<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ResourceParserGenerator\Contracts\Converters\DocBlockTypeConverterContract;
use ResourceParserGenerator\Contracts\Parsers\DocBlockParserContract;
use ResourceParserGenerator\Contracts\Resolvers\ResolverContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use ResourceParserGenerator\DataObjects\DocBlockData;
use ResourceParserGenerator\Types;
use RuntimeException;

class DocBlockParser implements DocBlockParserContract
{
    public function __construct(
        private readonly DocBlockTypeConverterContract $docBlockTypeConverter,
        private readonly Lexer $phpDocLexer,
        private readonly PhpDocParser $phpDocParser,
    ) {
        //
    }

    public function parse(string $content, ResolverContract $resolver): DocBlockData
    {
        $content = trim($content);
        if (!$content) {
            return new DocBlockData(null, null, collect(), collect(), collect(), collect());
        }

        $tokens = $this->phpDocLexer->tokenize($content);

        /**
         * @var TokenIterator $tokenIterator
         */
        $tokenIterator = resolve(TokenIterator::class, ['tokens' => $tokens]);

        $docNode = $this->phpDocParser->parse($tokenIterator);

        return new DocBlockData(
            collect($docNode->children)
                ->filter(fn(PhpDocChildNode $node) => $node instanceof PhpDocTextNode)
                ->map(fn(PhpDocTextNode $node) => $node->text)
                ->join("\n"),
            $this->parseReturn($docNode, $resolver),
            $this->parseParams($docNode, $resolver),
            $this->parseProperties($docNode, $resolver),
            $this->parseMethods($docNode, $resolver),
            $this->parseVars($docNode, $resolver),
        );
    }

    /**
     * @param PhpDocNode $docNode
     * @param ResolverContract $resolver
     * @return Collection<string, TypeContract>
     */
    private function parseMethods(PhpDocNode $docNode, ResolverContract $resolver): Collection
    {
        $methods = collect();

        $methodNodes = $docNode->getMethodTagValues();
        foreach ($methodNodes as $node) {
            $methods->put(
                $node->methodName,
                $node->returnType
                    ? $this->docBlockTypeConverter->convert($node->returnType, $resolver)
                    : new Types\UntypedType(),
            );
        }

        return $methods;
    }

    /**
     * @param PhpDocNode $docNode
     * @param ResolverContract $resolver
     * @return Collection<string, TypeContract>
     */
    private function parseParams(PhpDocNode $docNode, ResolverContract $resolver): Collection
    {
        $params = collect();

        $paramNodes = $docNode->getParamTagValues();
        foreach ($paramNodes as $node) {
            $name = ltrim($node->parameterName, '$');
            $params->put($name, $this->docBlockTypeConverter->convert($node->type, $resolver));
        }

        return $params;
    }

    /**
     * @param PhpDocNode $docNode
     * @param ResolverContract $resolver
     * @return Collection<string, TypeContract>
     */
    private function parseProperties(PhpDocNode $docNode, ResolverContract $resolver): Collection
    {
        $properties = collect();

        $propertyNodes = collect([
            ...$docNode->getPropertyTagValues(),
            ...$docNode->getPropertyReadTagValues(),
        ]);
        foreach ($propertyNodes as $node) {
            $name = ltrim($node->propertyName, '$');
            $properties->put($name, $this->docBlockTypeConverter->convert($node->type, $resolver));
        }

        return $properties;
    }

    /**
     * @param PhpDocNode $docNode
     * @param ResolverContract $resolver
     * @return ?TypeContract
     */
    private function parseReturn(PhpDocNode $docNode, ResolverContract $resolver): ?TypeContract
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
                return $this->docBlockTypeConverter->convert($returnNode->value->type, $resolver);
            }
        }

        return null;
    }

    /**
     * @param PhpDocNode $docNode
     * @param ResolverContract $resolver
     * @return Collection<string, TypeContract>
     */
    private function parseVars(PhpDocNode $docNode, ResolverContract $resolver): Collection
    {
        $vars = collect();

        $varNodes = $docNode->getVarTagValues();
        foreach ($varNodes as $node) {
            $name = trim($node->variableName);
            if ($name) {
                $name = ltrim($node->variableName, '$');
            }

            $vars->put($name, $this->docBlockTypeConverter->convert($node->type, $resolver));
        }

        return $vars;
    }
}
