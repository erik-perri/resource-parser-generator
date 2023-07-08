<?php

/** @noinspection PhpMissingReturnTypeInspection */

declare(strict_types=1);

namespace ResourceParserGenerator\DataObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class ReadOnlyCollection extends Collection
{
    /**
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue>|null $items
     * @return void
     */
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public function add($item)
    {
        $this->throwReadOnlyException();
    }

    public function offsetSet($key, $value): void
    {
        $this->throwReadOnlyException();
    }

    public function offsetUnset($key): void
    {
        $this->throwReadOnlyException();
    }

    public function put($key, $value)
    {
        $this->throwReadOnlyException();
    }

    public function pop($count = 1)
    {
        $this->throwReadOnlyException();
    }

    public function prepend($value, $key = null)
    {
        $this->throwReadOnlyException();
    }

    public function pull($key, $default = null)
    {
        $this->throwReadOnlyException();
    }

    public function push(...$values)
    {
        $this->throwReadOnlyException();
    }

    public function shift($count = 1)
    {
        $this->throwReadOnlyException();
    }

    public function splice($offset, $length = null, $replacement = [])
    {
        $this->throwReadOnlyException();
    }

    public function transform(callable $callback)
    {
        $this->throwReadOnlyException();
    }

    private function throwReadOnlyException(): never
    {
        throw new RuntimeException('This collection is read-only');
    }
}
