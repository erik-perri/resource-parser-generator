@php
    use Illuminate\Support\Collection;
    use ResourceParserGenerator\Contexts\ParserGeneratorContext;
    use ResourceParserGenerator\Contracts\ImportGroupContract;
    use ResourceParserGenerator\DataObjects\ParserData;

    /** @var ParserGeneratorContext $context */
    /** @var Collection<int, ImportGroupContract> $imports */
    /** @var ParserData $parser */
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

@include('resource-parser-generator::resource-parser', [
    'context' => $context,
    'properties' => $parser->properties->all(),
    'typeName' => $parser->configuration->typeName,
    'variableName' => $parser->configuration->variableName,
])
