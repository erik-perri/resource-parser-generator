@php
    use Illuminate\Support\Collection;
    use ResourceParserGenerator\Contexts\ResourceGeneratorContext;
    use ResourceParserGenerator\Contracts\ImportGroupContract;
    use ResourceParserGenerator\DataObjects\ResourceData;

    /** @var ResourceGeneratorContext $context */
    /** @var Collection<int, ImportGroupContract> $imports */
    /** @var Collection<string, ResourceData> $parsers */
@endphp

@foreach($imports as $module)
@php
    $line = [];
    if ($module->defaultImport()) {
        $line[] = $module->defaultImport();
    }
    if (count($module->imports())) {
        $line[] = '{'.implode(', ', $module->imports()).'}';
    }
@endphp
import {{ implode(', ', $line) }} from '{{$module->module()}}';
@endforeach

@foreach($parsers as $parserName => $parser)
@include('resource-parser-generator::resource-parser', [
    'context' => $context,
    'properties' => $parser->properties->all(),
    'typeName' => $parser->configuration->typeName,
    'variableName' => $parser->configuration->variableName,
])

@endforeach
