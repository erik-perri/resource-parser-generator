@php
    use ResourceParserGenerator\Builders\ParserBuilder;

    /** @var string[] $imports */
    /** @var ParserBuilder[] $parsers */
@endphp

import {{ '{' }}{{ implode(', ', $imports) }}{{ '}' }} from 'zod';

@foreach($parsers as $parser)
{!! $parser->create() !!}
@endforeach
