<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;
use function oihana\commands\helpers\escapeForPrintf;

final class EscapeForPrintfTest extends TestCase
{
    public function testEscapeNoQuotes()
    {
        $input = "Hello World";
        $expected = "'Hello World'";
        $this->assertSame($expected, escapeForPrintf($input));
    }

    public function testEscapeWithSingleQuotes()
    {
        $input = "It's a test";
        $expected = "'It'\\''s a test'";
        $this->assertSame($expected, escapeForPrintf($input));
    }

    public function testEscapeMultipleQuotes()
    {
        $input = "Quote 'one' and 'two'";
        $expected = "'Quote '\\''one'\\'' and '\\''two'\\'''";
        $this->assertSame($expected, escapeForPrintf($input));
    }

    public function testEscapeEmptyString()
    {
        $input = "";
        $expected = "''";
        $this->assertSame($expected, escapeForPrintf($input));
    }
}
