<?php

namespace tests\oihana\commands\enums;

use PHPUnit\Framework\TestCase;

use oihana\commands\enums\outputs\Palette;
use ReflectionClass;

class PaletteTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(Palette::class . '::BLACK'), 'The BLACK constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::BLUE'), 'The BLUE constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::CYAN'), 'The CYAN constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::DEFAULT'), 'The DEFAULT constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::GREEN'), 'The GREEN constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::MAGENTA'), 'The MAGENTA constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::RED'), 'The RED constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::YELLOW'), 'The YELLOW constant must be defined.');
        $this->assertTrue(defined(Palette::class . '::WHITE'), 'The WHITE constant must be defined.');
    }

    public function testConstantValues(): void
    {
        $this->assertSame('black', Palette::BLACK, 'BLACK must equal "black".');
        $this->assertSame('blue', Palette::BLUE, 'BLUE must equal "blue".');
        $this->assertSame('cyan', Palette::CYAN, 'CYAN must equal "cyan".');
        $this->assertSame('default', Palette::DEFAULT, 'DEFAULT must equal "default".');
        $this->assertSame('green', Palette::GREEN, 'GREEN must equal "green".');
        $this->assertSame('magenta', Palette::MAGENTA, 'MAGENTA must equal "magenta".');
        $this->assertSame('red', Palette::RED, 'RED must equal "red".');
        $this->assertSame('yellow', Palette::YELLOW, 'YELLOW must equal "yellow".');
        $this->assertSame('white', Palette::WHITE, 'WHITE must equal "white".');
    }

    public function testAllConstants(): void
    {
        // Check that ConstantsTrait provides all constants if getConstants() exists
            $constants = Palette::getAll();

            $expected = [
                'BLACK'   => 'black',
                'BLUE'    => 'blue',
                'CYAN'    => 'cyan',
                'DEFAULT' => 'default',
                'GREEN'   => 'green',
                'MAGENTA' => 'magenta',
                'RED'     => 'red',
                'YELLOW'  => 'yellow',
                'WHITE'   => 'white',
            ];

            $this->assertSame($expected, $constants, 'The constants returned by getConstants() must be correct.');
    }

    public function testNumberOfConstants(): void
    {
        $reflection = new ReflectionClass(Palette::class);
        $constants = $reflection->getConstants();

        $this->assertCount(9, $constants, 'Palette must contain exactly 9 constants.');
    }
}