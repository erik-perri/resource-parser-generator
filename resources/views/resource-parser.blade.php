@php
    /** @var array<string, string> $properties */
    /** @var string $shortClassName */
    /** @var string $methodName */

    use Illuminate\Support\Str;
    $parserName = Str::camel($shortClassName) . Str::studly($methodName) . 'Parser';
    $typeName = $shortClassName . Str::studly($methodName);
@endphp

export const {{ $parserName }} = object({
@foreach($properties as $name => $type)
  {{ $name }}: {{ $type }},
@endforeach
});

export type {{ $typeName }} = output{!! '<' !!}typeof {{ $parserName }}{!! '>' !!};
