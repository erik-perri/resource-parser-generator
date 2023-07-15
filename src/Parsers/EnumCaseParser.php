<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Parsers;

use BenSampo\Enum\Enum;
use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ClassConstantContract;
use ResourceParserGenerator\Contracts\EnumScopeContract;
use ResourceParserGenerator\Contracts\Parsers\EnumCaseParserContract;
use ResourceParserGenerator\DataObjects\EnumCaseData;
use ResourceParserGenerator\Parsers\Data\ClassConstant;
use RuntimeException;

class EnumCaseParser implements EnumCaseParserContract
{
    public function __construct(
        private readonly ClassParser $classParser,
    ) {
        //
    }

    /**
     * @param class-string $className
     * @return Collection<int, EnumCaseData>
     */
    public function parse(string $className): Collection
    {
        $classScope = $this->classParser->parse($className);

        if ($classScope instanceof EnumScopeContract) {
            return $classScope->cases();
        }

        if (!$classScope->hasParent(Enum::class)) {
            throw new RuntimeException(sprintf('Class "%s" is not a known enum type.', $className));
        }

        return $classScope->constants()
            ->map(function (ClassConstantContract $constant, string $name) use ($className) {
                if (!($constant instanceof ClassConstant)) {
                    throw new RuntimeException(sprintf(
                        'Constant "%s" on class "%s" is not a concrete property.',
                        $name,
                        $className,
                    ));
                }
                return new EnumCaseData(
                    $constant->name(),
                    $constant->value(),
                    $constant->comment(),
                );
            })
            ->values();
    }
}
