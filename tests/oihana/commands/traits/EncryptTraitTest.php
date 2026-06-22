<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\options\CommandOption;
use oihana\commands\traits\EncryptTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Unit tests for EncryptTrait
 */
class EncryptTraitTest extends TestCase
{
    private object $trait ;

    protected function setUp(): void
    {
        $this->trait = new class { use EncryptTrait; } ;
    }

    private function inputWithOption( mixed $value ): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;
        $input->method( 'getOption' )->willReturn( $value ) ;
        return $input ;
    }

    public function testEncryptDefaultsToTrue(): void
    {
        $this->assertTrue( $this->trait->encrypt ) ;
    }

    public function testInitializeEncryptOverridesDefault(): void
    {
        $result = $this->trait->initializeEncrypt( [ CommandOption::ENCRYPT => false ] ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertFalse( $this->trait->encrypt ) ;
    }

    public function testInitializeEncryptKeepsDefaultWhenAbsent(): void
    {
        $this->trait->initializeEncrypt( [] ) ;
        $this->assertTrue( $this->trait->encrypt ) ;
    }

    public function testShouldEncryptReturnsOptionWhenProvided(): void
    {
        $this->assertFalse( $this->trait->shouldEncrypt( $this->inputWithOption( false ) ) ) ;
        $this->assertTrue( $this->trait->shouldEncrypt( $this->inputWithOption( true ) ) ) ;
    }

    public function testShouldEncryptFallsBackToFlagWhenOptionNull(): void
    {
        $this->trait->encrypt = true ;
        $this->assertTrue( $this->trait->shouldEncrypt( $this->inputWithOption( null ) ) ) ;

        $this->trait->encrypt = false ;
        $this->assertFalse( $this->trait->shouldEncrypt( $this->inputWithOption( null ) ) ) ;
    }
}
