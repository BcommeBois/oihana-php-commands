<?php

namespace tests\oihana\commands\enums;

use PHPUnit\Framework\TestCase;

use oihana\commands\enums\outputs\ColorParam;
use ReflectionClass;

class ColorParamTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue( defined(ColorParam::class . '::FOREGROUND' ) , 'The FOREGROUND constant must be defined.' );
        $this->assertTrue( defined(ColorParam::class . '::BACKGROUND' ) , 'The BACKGROUND constant must be defined.' );
        $this->assertTrue( defined(ColorParam::class . '::OPTIONS'    ) , 'The OPTIONS constant must be defined.'    );
    }

    public function testConstantValues(): void
    {
        $this->assertSame('foreground' , ColorParam::FOREGROUND , 'FOREGROUND must equal "foreground".' ) ;
        $this->assertSame('background' , ColorParam::BACKGROUND , 'BACKGROUND must equal "background".' ) ;
        $this->assertSame('options'    , ColorParam::OPTIONS    , 'OPTIONS must equal "options".'       ) ;
    }


    public function testNumberOfConstants(): void
    {
        $reflection = new ReflectionClass(ColorParam::class);
        $constants = $reflection->getConstants();
        $this->assertCount(3, $constants, 'ColorParam must contain exactly 3 constants.');
    }
}