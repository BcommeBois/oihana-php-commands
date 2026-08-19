<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use InvalidArgumentException;

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

    /**
     * Returns an input defining the `--encrypt` option with the given value.
     */
    private function inputWithOption( mixed $value ): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;
        $input->method( 'hasOption' )->willReturn( true ) ;
        $input->method( 'getOption' )->willReturn( $value ) ;
        return $input ;
    }

    /**
     * Returns an input that does not define the `--encrypt` option, and that
     * fails - as Symfony does - if `getOption()` is called anyway.
     */
    private function inputWithoutOption(): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;
        $input->method( 'hasOption' )->willReturn( false ) ;
        $input->method( 'getOption' )->willThrowException( new InvalidArgumentException( 'The "--encrypt" option does not exist.' ) ) ;
        return $input ;
    }

    /**
     * Invokes the protected EncryptTrait::resolveEncrypt() method.
     */
    private function resolveEncrypt( InputInterface $input , ?bool $configured ): bool
    {
        $trait = $this->trait ;
        return ( fn() => $this->resolveEncrypt( $input , $configured ) )->call( $trait ) ;
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

    public function testShouldEncryptFallsBackToFlagWhenOptionIsNotDefined(): void
    {
        $this->trait->encrypt = true ;
        $this->assertTrue( $this->trait->shouldEncrypt( $this->inputWithoutOption() ) ) ;

        $this->trait->encrypt = false ;
        $this->assertFalse( $this->trait->shouldEncrypt( $this->inputWithoutOption() ) ) ;
    }

    public function testResolveEncryptPrefersTheConsoleOption(): void
    {
        $this->trait->encrypt = false ;
        $this->assertTrue( $this->resolveEncrypt( $this->inputWithOption( true ) , false ) ) ;

        $this->trait->encrypt = true ;
        $this->assertFalse( $this->resolveEncrypt( $this->inputWithOption( false ) , true ) ) ;
    }

    public function testResolveEncryptCastsTheOptionValueToBoolean(): void
    {
        $this->assertTrue ( $this->resolveEncrypt( $this->inputWithOption( '1' ) , false ) ) ;
        $this->assertFalse( $this->resolveEncrypt( $this->inputWithOption( '0' ) , true  ) ) ;
    }

    public function testResolveEncryptUsesTheConfiguredValueWhenTheOptionIsNotGiven(): void
    {
        $this->trait->encrypt = true ;
        $this->assertFalse( $this->resolveEncrypt( $this->inputWithOption( null ) , false ) ) ;

        $this->trait->encrypt = false ;
        $this->assertTrue( $this->resolveEncrypt( $this->inputWithOption( null ) , true ) ) ;
    }

    public function testResolveEncryptUsesTheConfiguredValueWhenTheOptionIsNotDefined(): void
    {
        $this->trait->encrypt = true ;
        $this->assertFalse( $this->resolveEncrypt( $this->inputWithoutOption() , false ) ) ;

        $this->trait->encrypt = false ;
        $this->assertTrue( $this->resolveEncrypt( $this->inputWithoutOption() , true ) ) ;
    }

    public function testResolveEncryptKeepsTheDefaultFlagWhenNothingIsConfigured(): void
    {
        $this->trait->encrypt = true ;
        $this->assertTrue( $this->resolveEncrypt( $this->inputWithOption( null ) , null ) ) ;

        $this->trait->encrypt = false ;
        $this->assertFalse( $this->resolveEncrypt( $this->inputWithoutOption() , null ) ) ;
    }
}
