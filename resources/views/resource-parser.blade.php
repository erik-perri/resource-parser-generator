@php
    use ResourceParserGenerator\Contexts\ResourceGeneratorContext;
    use ResourceParserGenerator\Contracts\Types\ParserTypeContract;
    use ResourceParserGenerator\Contracts\Types\ParserTypeWithCommentContract;

    /** @var ResourceGeneratorContext $context */
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
  {{ $name }}: {{ $type->constraint($context) }},
@endforeach
});

export type {{ $typeName }} = output{!! '<' !!}typeof {{ $variableName }}{!! '>' !!};
