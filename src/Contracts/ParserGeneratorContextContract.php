<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Contracts;

use Closure;
use Illuminate\Support\Collection;
use ResourceParserGenerator\DataObjects\ParserData;

interface ParserGeneratorContextContract
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @return ParserData|null
     */
    public function find(string $className, string $methodName): ParserData|null;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return ParserData|null
     */
    public function findLocal(string $className, string $methodName): ParserData|null;

    /**
     * @template T
     * @param Collection<int, ParserData> $localParsers
     * @param Closure(): T $callback
     * @return T
     */
    public function withLocalContext(Collection $localParsers, Closure $callback): mixed;
}
