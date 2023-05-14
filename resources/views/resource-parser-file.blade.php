@php
    use Illuminate\Support\Collection;use ResourceParserGenerator\Parsers\Data\ResourceParserData;

    /** @var string[] $imports */
    /** @var Collection<string, ResourceParserData> $parsers */
@endphp

import {{ '{' }}{{ implode(', ', $imports) }}{{ '}' }} from 'zod';

@foreach($parsers as $parserName => $parser)
@include('resource-parser-generator::resource-parser', [
    'properties' => $parser->properties(),
    'typeName' => $parser->typeName(),
    'variableName' => $parser->variableName(),
])

@endforeach
