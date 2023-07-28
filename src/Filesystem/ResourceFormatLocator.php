<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ResourceParserGenerator\Contracts\ClassScopeContract;
use ResourceParserGenerator\Contracts\Filesystem\ResourceFormatLocatorContract;
use ResourceParserGenerator\Contracts\Parsers\PhpFileParserContract;
use ResourceParserGenerator\DataObjects\ResourceFormat;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ResourceFormatLocator implements ResourceFormatLocatorContract
{
    public function __construct(
        private readonly PhpFileParserContract $fileParser,
    ) {
        //
    }

    /**
     * @param string $fileName
     * @return Collection<int, ResourceFormat>
     */
    public function formatsInFile(string $fileName): Collection
    {
        $fileScope = $this->fileParser->parse(File::get($fileName));
        $classes = $fileScope->classes();
        if ($classes->isEmpty()) {
            throw new RuntimeException(sprintf('No classes found in file "%s"', $fileName));
        }

        $class = $classes->firstOrFail();
        if (!$class->hasParent(Resource::class)) {
            return collect();
        }

        return $this->formatsInClass($class);
    }

    /**
     * @param ClassScopeContract $class
     * @return Collection<int, ResourceFormat>
     */
    public function formatsInClass(ClassScopeContract $class): Collection
    {
        $formats = collect();

        foreach ($class->methods() as $methodName => $methodScope) {
            $defaultAttribute = $methodScope->attribute(IsDefault::class);
            $formatAttribute = $methodScope->attribute(Format::class);
            if ($formatAttribute) {
                $formatName = $formatAttribute->argument(0);
                if (!is_string($formatName)) {
                    throw new RuntimeException(sprintf('Unhandled non-string format name "%s"', gettype($formatName)));
                }
                $formats->add(new ResourceFormat(
                    $class->fullyQualifiedName(),
                    $methodName,
                    (bool)$defaultAttribute,
                    $formatName,
                ));
            } elseif ($defaultAttribute) {
                $formats->add(new ResourceFormat(
                    $class->fullyQualifiedName(),
                    $methodName,
                    true,
                    null,
                ));
            }
        }

        if ($formats->count() === 1) {
            /** @var ResourceFormat $firstFormat */
            $firstFormat = $formats->first();
            return collect([
                new ResourceFormat(
                    $firstFormat->className,
                    $firstFormat->methodName,
                    true,
                    $firstFormat->formatName,
                ),
            ]);
        }

        return $formats;
    }
}
