<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Generators;

use ResourceParserGenerator\Contracts\Generators\EnumGeneratorContract;
use ResourceParserGenerator\DataObjects\EnumData;
use RuntimeException;

/**
 * TODO Replace with AST based generation
 */
class EnumGenerator implements EnumGeneratorContract
{
    /**
     * @param EnumData $enum
     * @return string
     */
    public function generate(EnumData $enum): string
    {
        $content[] = sprintf('const %s = {', $enum->configuration->typeName);

        foreach ($enum->cases as $case) {
            $renderedValue = match (gettype($case->value)) {
                'string' => sprintf("'%s'", $case->value),
                'integer' => (string)$case->value,
                default => throw new RuntimeException(sprintf(
                    'Unhandled enum type "%s" for %s::%s.',
                    gettype($case->value),
                    $enum->configuration->className,
                    $case->name,
                )),
            };
            if ($case->comment) {
                $content[] = '  /**';
                foreach (explode("\n", $case->comment) as $line) {
                    $line = trim($line);
                    $line = $line ? ' ' . $line : $line;
                    $content[] = sprintf('   *%s', $line);
                }
                $content[] = '   */';
            }
            $content[] = sprintf("  %s: %s,", $case->name, $renderedValue);
        }

        $content[] = '} as const;';
        $content[] = '';
        $content[] = sprintf('type %1$s = typeof %1$s[keyof typeof %1$s];', $enum->configuration->typeName);
        $content[] = '';
        $content[] = sprintf('export default %s;', $enum->configuration->typeName);

        return trim(implode("\n", $content)) . "\n";
    }
}
