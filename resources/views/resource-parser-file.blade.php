@php
    use Illuminate\Support\Collection;
    use ResourceParserGenerator\DataObjects\ResourceData;

    /** @var Collection<string, string[]> $imports */
    /** @var Collection<string, ResourceData> $parsers */
@endphp

@foreach($imports as $from => $imported)
import {{ '{' }}{{ implode(', ', $imported) }}{{ '}' }} from '{{$from}}';
@endforeach

@foreach($parsers as $parserName => $parser)
@include('resource-parser-generator::resource-parser', [
    'properties' => $parser->properties(),
    'typeName' => $parser->configuration->outputType,
    'variableName' => $parser->configuration->outputVariable,
])

@endforeach
