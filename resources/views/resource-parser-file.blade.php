@php
    use Illuminate\Support\Collection;
    use ResourceParserGenerator\DataObjects\ResourceContext;

    /** @var Collection<string, string[]> $imports */
    /** @var Collection<string, ResourceContext> $parsers */
@endphp

@foreach($imports as $from => $imported)
import {{ '{' }}{{ implode(', ', $imported) }}{{ '}' }} from '{{$from}}';
@endforeach

@foreach($parsers as $parserName => $parser)
@include('resource-parser-generator::resource-parser', [
    'properties' => $parser->parserData->properties(),
    'typeName' => $parser->configuration->outputType,
    'variableName' => $parser->configuration->outputVariable,
])

@endforeach
