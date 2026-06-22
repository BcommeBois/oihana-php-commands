<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\exceptions\MissingPassphraseException;
use oihana\commands\options\CommandOption;
use oihana\commands\traits\PassphraseTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Consumer of PassphraseTrait exposing the protected passphrase and overriding getIO()
 * (from IOTrait) so the interactive branch can be driven by a mocked SymfonyStyle.
 */
class PassphraseFixture
{
    use PassphraseTrait;

    public ?SymfonyStyle $mockIO = null ;

    public function setPass( ?string $passphrase ): void
    {
        $this->passphrase = $passphrase ;
    }

    public function getIO( InputInterface $input , OutputInterface $output ): SymfonyStyle
    {
        return $this->mockIO ;
    }
}

/**
 * Unit tests for PassphraseTrait
 */
class PassphraseTraitTest extends TestCase
{
    private function makeInput( bool $hasOption , mixed $optionValue = null , bool $interactive = false ): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;
        $input->method( 'hasOption' )->willReturn( $hasOption ) ;
        $input->method( 'getOption' )->willReturn( $optionValue ) ;
        $input->method( 'isInteractive' )->willReturn( $interactive ) ;
        return $input ;
    }

    public function testReturnsInternalPassphraseWhenOptionAbsentAndNonInteractive(): void
    {
        $fixture = new PassphraseFixture() ;
        $fixture->setPass( 'secret' ) ;

        $result = $fixture->getPassPhrase( $this->makeInput( false ) , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'secret' , $result ) ;
    }

    public function testReadsPassphraseFromOption(): void
    {
        $fixture = new PassphraseFixture() ;

        $result = $fixture->getPassPhrase( $this->makeInput( true , 'from-option' ) , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'from-option' , $result ) ;
    }

    public function testOptionNullFallsBackToInternalPassphrase(): void
    {
        $fixture = new PassphraseFixture() ;
        $fixture->setPass( 'internal' ) ;

        $result = $fixture->getPassPhrase( $this->makeInput( true , null ) , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'internal' , $result ) ;
    }

    public function testThrowsWhenMissingAndThrowable(): void
    {
        $fixture = new PassphraseFixture() ;
        $fixture->setPass( null ) ;

        $this->expectException( MissingPassphraseException::class ) ;
        $this->expectExceptionMessage( 'The passphrase is required.' ) ;

        $fixture->getPassPhrase( $this->makeInput( false ) , $this->createStub( OutputInterface::class ) ) ;
    }

    public function testReturnsNullWhenMissingAndNotThrowable(): void
    {
        $fixture = new PassphraseFixture() ;
        $fixture->setPass( null ) ;

        $result = $fixture->getPassPhrase( $this->makeInput( false ) , $this->createStub( OutputInterface::class ) , false ) ;

        $this->assertNull( $result ) ;
    }

    public function testPromptsInteractivelyWhenPassphraseEmpty(): void
    {
        $io = $this->createMock( SymfonyStyle::class ) ;
        $io->expects( $this->once() )->method( 'newLine' ) ;
        $io->method( 'askHidden' )->willReturn( 'typed-secret' ) ;

        $fixture = new PassphraseFixture() ;
        $fixture->mockIO = $io ;
        $fixture->setPass( null ) ;

        $result = $fixture->getPassPhrase( $this->makeInput( false , null , true ) , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'typed-secret' , $result ) ;
    }

    public function testInitializePassphraseSetsValue(): void
    {
        $fixture = new PassphraseFixture() ;

        $result = $fixture->initializePassphrase( [ CommandOption::PASS_PHRASE => 'configured' ] ) ;

        $this->assertSame( $fixture , $result ) ;
        $this->assertSame
        (
            'configured' ,
            $fixture->getPassPhrase( $this->makeInput( false ) , $this->createStub( OutputInterface::class ) , false )
        );
    }

    public function testInitializePassphraseKeepsCurrentValueWhenAbsent(): void
    {
        $fixture = new PassphraseFixture() ;
        $fixture->setPass( 'original' ) ;

        $fixture->initializePassphrase( [] ) ;

        $this->assertSame
        (
            'original' ,
            $fixture->getPassPhrase( $this->makeInput( false ) , $this->createStub( OutputInterface::class ) , false )
        );
    }
}
