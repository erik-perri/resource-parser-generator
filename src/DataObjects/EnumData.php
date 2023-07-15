<?php

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Support\Collection;

class EnumData
{
    /**
     * @var ReadOnlyCollection<int, EnumCaseData>
     */
    public readonly ReadOnlyCollection $cases;

    /**
     * @param EnumConfiguration $configuration
     * @param Collection<int, EnumCaseData> $cases
     */
    public function __construct(
        public readonly EnumConfiguration $configuration,
        Collection $cases,
    ) {
        $this->cases = new ReadOnlyCollection($cases->all());
    }
}
