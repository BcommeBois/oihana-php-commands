<?php

namespace oihana\commands\enums;

use PHPUnit\Framework\TestCase;

use oihana\commands\enums\outputs\StyleOption;
use ReflectionClass;

class StyleOptionTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(StyleOption::class . '::BLINK'), 'The BLINK constant must be defined.');
        $this->assertTrue(defined(StyleOption::class . '::BOLD'), 'The BOLD constant must be defined.');
        $this->assertTrue(defined(StyleOption::class . '::CONCEAL'), 'The CONCEAL constant must be defined.');
        $this->assertTrue(defined(StyleOption::class . '::REVERSE'), 'The REVERSE constant must be defined.');
        $this->assertTrue(defined(StyleOption::class . '::UNDERSCORE'), 'The UNDERSCORE constant must be defined.');
    }

    public function testConstantValues(): void
    {
        $this->assertSame('blink', StyleOption::BLINK, 'BLINK must equal "blink".');
        $this->assertSame('bold', StyleOption::BOLD, 'BOLD must equal "bold".');
        $this->assertSame('conceal', StyleOption::CONCEAL, 'CONCEAL must equal "conceal".');
        $this->assertSame('reverse', StyleOption::REVERSE, 'REVERSE must equal "reverse".');
        $this->assertSame('underscore', StyleOption::UNDERSCORE, 'UNDERSCORE must equal "underscore".');
    }

    public function testAllConstants(): void
    {
        $constants = StyleOption::getAll();

        $expected = [
            'BLINK'      => 'blink',
            'BOLD'       => 'bold',
            'CONCEAL'    => 'conceal',
            'REVERSE'    => 'reverse',
            'UNDERSCORE' => 'underscore',
        ];

        $this->assertSame($expected, $constants, 'The constants returned by getConstants() must be correct.');
    }

    public function testNumberOfConstants(): void
    {
        $reflection = new ReflectionClass(StyleOption::class);
        $constants = $reflection->getConstants();

        $this->assertCount(5, $constants, 'StyleOption must contain exactly 5 constants.');
    }
}