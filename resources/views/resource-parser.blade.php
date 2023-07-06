@php
    use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
    use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;

    /** @var array<string, ParserTypeContract> $properties */
    /** @var string $variableName */
    /** @var string $typeName */
@endphp

export const {{ $variableName }} = object({
@foreach($properties as $name => $type)
@if ($type instanceof ParserTypeWithCommentContract && $type->comment())
  /**
   * {!! $type->comment() !!}
   */
@endif
  {{ $name }}: {{ $type->constraint() }},
@endforeach
});

export type {{ $typeName }} = output{!! '<' !!}typeof {{ $variableName }}{!! '>' !!};
