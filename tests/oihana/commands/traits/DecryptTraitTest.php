<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\options\CommandOption;
use oihana\commands\traits\DecryptTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Unit tests for DecryptTrait
 */
class DecryptTraitTest extends TestCase
{
    private object $trait ;

    protected function setUp(): void
    {
        $this->trait = new class { use DecryptTrait; } ;
    }

    private function inputWithOption( mixed $value ): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;
        $input->method( 'getOption' )->willReturn( $value ) ;
        return $input ;
    }

    public function testDecryptDefaultsToTrue(): void
    {
        $this->assertTrue( $this->trait->decrypt ) ;
    }

    public function testInitializeDecryptOverridesDefault(): void
    {
        $result = $this->trait->initializeDecrypt( [ CommandOption::DECRYPT => false ] ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertFalse( $this->trait->decrypt ) ;
    }

    public function testInitializeDecryptKeepsDefaultWhenAbsent(): void
    {
        $this->trait->initializeDecrypt( [] ) ;
        $this->assertTrue( $this->trait->decrypt ) ;
    }

    public function testShouldDecryptReturnsTrueWhenOptionTrue(): void
    {
        $this->trait->decrypt = false ;
        $this->assertTrue( $this->trait->shouldDecrypt( $this->inputWithOption( true ) ) ) ;
    }

    public function testShouldDecryptFallsBackToFlagWhenOptionFalsy(): void
    {
        $this->trait->decrypt = true ;
        $this->assertTrue( $this->trait->shouldDecrypt( $this->inputWithOption( false ) ) ) ;

        $this->trait->decrypt = false ;
        $this->assertFalse( $this->trait->shouldDecrypt( $this->inputWithOption( false ) ) ) ;
    }
}
