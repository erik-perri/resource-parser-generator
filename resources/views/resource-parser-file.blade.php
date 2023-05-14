@php
    use Illuminate\Support\Collection;use ResourceParserGenerator\Parsers\Data\ResourceParserData;

    /** @var Collection<string, string[]> $imports */
    /** @var Collection<string, ResourceParserData> $parsers */
@endphp

@foreach($imports as $from => $imported)
import {{ '{' }}{{ implode(', ', $imported) }}{{ '}' }} from '{{$from}}';
@endforeach

@foreach($parsers as $parserName => $parser)
@include('resource-parser-generator::resource-parser', [
    'properties' => $parser->properties(),
    'typeName' => $parser->typeName(),
    'variableName' => $parser->variableName(),
])

@endforeach
