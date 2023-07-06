<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Types;

use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
use ResourceParserGenerator\Contracts\Types\TypeContract;
use Throwable;

class ErrorType implements TypeContract
{
    public function __construct(private readonly Throwable $exception)
    {
        //
    }

    public function describe(): string
    {
        return 'error';
    }

    public function parserType(): ParserTypeContract
    {
        return (new Zod\ZodUnknownType())
            ->setComment(sprintf('Error: %s', $this->exception->getMessage()));
    }
}
