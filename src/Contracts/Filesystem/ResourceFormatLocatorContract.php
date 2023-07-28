<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts\Filesystem;

use Illuminate\Support\Collection;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\DataObjects\ResourceFormat;

interface ResourceFormatLocatorContract
{
    /**
     * @param ClassScopeContract $class
     * @return Collection<int, ResourceFormat>
     */
    public function formatsInClass(ClassScopeContract $class): Collection;

    /**
     * @param string $fileName
     * @return Collection<int, ResourceFormat>
     */
    public function formatsInFile(string $fileName): Collection;
}
