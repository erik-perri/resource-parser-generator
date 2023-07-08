<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Filesystem;

use Illuminate\Support\Facades\File;
use ResourceParserGenerator\Parsers\PhpFileParser;
use RuntimeException;
use Sourcetoad\EnhancedResources\Formatting\Attributes\Format;
use Sourcetoad\EnhancedResources\Formatting\Attributes\IsDefault;
use Sourcetoad\EnhancedResources\Resource;

class ResourceFileFormatLocator
{
    public function __construct(
        private readonly PhpFileParser $fileParser,
    ) {
        //
    }

    /**
     * @param string $fileName
     * @return array<array{class-string, string}>
     */
    public function formats(string $fileName): array
    {
        $fileScope = $this->fileParser->parse(File::get($fileName));
        $classes = $fileScope->classes();
        if ($classes->isEmpty()) {
            throw new RuntimeException(sprintf('No classes found in file "%s"', $fileName));
        }

        /**
         * @var array<array{class-string, string}> $formats
         */
        $formats = [];
        $class = $classes->firstOrFail();
        if (!$class->hasParent(Resource::class)) {
            return [];
        }

        foreach ($class->methods() as $methodName => $methodScope) {
            if ($methodScope->attribute(Format::class) || $methodScope->attribute(IsDefault::class)) {
                $formats[] = [$class->fullyQualifiedName(), $methodName];
            }
        }

        return $formats;
    }
}
