<?php

declare(strict_types=1);

namespace ResourceParserGenerator\Tests\Unit\Parsers;

use ResourceParserGenerator\Parsers\ClassParser;
use ResourceParserGenerator\Parsers\EnumCaseParser;
use ResourceParserGenerator\Tests\Examples\Enums\LegacyPostStatus;
use ResourceParserGenerator\Tests\Examples\Enums\PostStatus;
use ResourceParserGenerator\Tests\TestCase;

class EnumCaseParserTest extends TestCase
{
    public function testParsesLegacyEnums(): void
    {
        // Arrange
        $parser = new EnumCaseParser(resolve(ClassParser::class));

        // Act
        $cases = $parser->parse(LegacyPostStatus::class);

        // Assert
        $this->assertCount(3, $cases);
        $this->assertSame('DRAFT', $cases->get(0)->name);
        $this->assertSame('draft', $cases->get(0)->value);
        $this->assertSame('In Draft', $cases->get(0)->comment);
        $this->assertSame('PUBLISHED', $cases->get(1)->name);
        $this->assertSame('published', $cases->get(1)->value);
        $this->assertSame(null, $cases->get(1)->comment);
        $this->assertSame('ARCHIVED', $cases->get(2)->name);
        $this->assertSame('archived', $cases->get(2)->value);
        $this->assertSame("With\n\nNew Lines", $cases->get(2)->comment);
    }

    public function testParsesEnums(): void
    {
        // Arrange
        $parser = new EnumCaseParser(resolve(ClassParser::class));

        // Act
        $cases = $parser->parse(PostStatus::class);

        // Assert
        $this->assertCount(3, $cases);
        $this->assertSame('Draft', $cases->get(0)->name);
        $this->assertSame('draft', $cases->get(0)->value);
        $this->assertSame('Draft', $cases->get(0)->comment);
        $this->assertSame('Published', $cases->get(1)->name);
        $this->assertSame('published', $cases->get(1)->value);
        $this->assertSame(null, $cases->get(1)->comment);
        $this->assertSame('Archived', $cases->get(2)->name);
        $this->assertSame('archived', $cases->get(2)->value);
        $this->assertSame("With\n\nNew Lines", $cases->get(2)->comment);
    }
}
