@php
    use ResourceParserGenerator\Contracts\Types\ParserTypeContract;

    /** @var array<string, ParserTypeContract> $properties */
    /** @var string $variableName */
    /** @var string $typeName */
@endphp

export const {{ $variableName }} = object({
@foreach($properties as $name => $type)
  {{ $name }}: {{ $type->constraint() }},
@endforeach
});

export type {{ $typeName }} = output{!! '<' !!}typeof {{ $variableName }}{!! '>' !!};
