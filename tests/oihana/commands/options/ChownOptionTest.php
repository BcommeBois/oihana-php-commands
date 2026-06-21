<?php

declare(strict_types=1);

namespace tests\oihana\commands\options;

use oihana\commands\options\ChownOption;
use PHPUnit\Framework\TestCase;

use function oihana\core\strings\hyphenate;
use function oihana\files\isMac;

final class ChownOptionTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame('from', ChownOption::FROM);
        $this->assertSame('group', ChownOption::GROUP);
        $this->assertSame('noDereference', ChownOption::NO_DEREFERENCE);
        $this->assertSame('owner', ChownOption::OWNER);
        $this->assertSame('path', ChownOption::PATH);
        $this->assertSame('reference', ChownOption::REFERENCE);
        $this->assertSame('recursive', ChownOption::RECURSIVE);
        $this->assertSame('sudo', ChownOption::SUDO);
        $this->assertSame('verbose', ChownOption::VERBOSE);
    }

    public function testGetCommandOptionRecursive(): void
    {
        $expected = isMac() ? 'R' : hyphenate(ChownOption::RECURSIVE);
        $this->assertSame($expected, ChownOption::getCommandOption(ChownOption::RECURSIVE));
    }

    public function testGetCommandOptionVerbose(): void
    {
        $expected = isMac() ? 'v' : hyphenate(ChownOption::VERBOSE);
        $this->assertSame($expected, ChownOption::getCommandOption(ChownOption::VERBOSE));
    }

    public function testGetCommandOptionPositionalOrDefault(): void
    {
        // On macOS, GROUP/OWNER/PATH are positional → empty string.
        // On Linux, they are hyphenated (single words → unchanged).
        $expected = isMac() ? '' : hyphenate(ChownOption::GROUP);
        $this->assertSame($expected, ChownOption::getCommandOption(ChownOption::GROUP));

        $expectedOwner = isMac() ? '' : hyphenate(ChownOption::OWNER);
        $this->assertSame($expectedOwner, ChownOption::getCommandOption(ChownOption::OWNER));
    }

    public function testGetCommandOptionNoDereference(): void
    {
        // Not in the macOS match → default (empty) on Mac; hyphenated on Linux.
        $expected = isMac() ? '' : hyphenate(ChownOption::NO_DEREFERENCE);
        $this->assertSame($expected, ChownOption::getCommandOption(ChownOption::NO_DEREFERENCE));

        if (!isMac())
        {
            $this->assertSame('no-dereference', ChownOption::getCommandOption(ChownOption::NO_DEREFERENCE));
        }
    }
}
