<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use DateTimeImmutable;

use oihana\commands\enums\ExitCode;
use oihana\commands\traits\LifecycleTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Consumer of LifecycleTrait. getIO() (inherited from IOTrait) is overridden to return a
 * SymfonyStyle backed by a BufferedOutput so the rendered text can be asserted.
 */
class LifecycleFixture
{
    use LifecycleTrait;

    public ?string $name   = 'mycmd' ;
    public ?string $action = null ;

    public BufferedOutput $buffer ;
    private SymfonyStyle   $style ;

    public function __construct()
    {
        $this->buffer = new BufferedOutput() ;
        $this->style  = new SymfonyStyle( new ArrayInput( [] ) , $this->buffer ) ;
    }

    public function getName(): ?string
    {
        return $this->name ;
    }

    public function getIO( InputInterface $input , OutputInterface $output ): SymfonyStyle
    {
        return $this->style ;
    }

    public function callStart( InputInterface $input , OutputInterface $output ): array
    {
        return $this->startCommand( $input , $output ) ;
    }
}

/**
 * Unit tests for LifecycleTrait
 */
class LifecycleTraitTest extends TestCase
{
    private function io(): array
    {
        return [ $this->createStub( InputInterface::class ) , $this->createStub( OutputInterface::class ) ] ;
    }

    public function testStartCommandRendersTitleWithName(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $result = $fixture->callStart( $input , $output ) ;

        $this->assertCount( 3 , $result ) ;
        $this->assertInstanceOf( SymfonyStyle::class , $result[ 0 ] ) ;
        $this->assertIsFloat( $result[ 1 ] ) ;
        $this->assertInstanceOf( DateTimeImmutable::class , $result[ 2 ] ) ;
        $this->assertStringContainsString( 'Mycmd' , $fixture->buffer->fetch() ) ;
    }

    public function testStartCommandAppendsActionToTitle(): void
    {
        $fixture = new LifecycleFixture() ;
        $fixture->action = 'build' ;
        [ $input , $output ] = $this->io() ;

        $fixture->callStart( $input , $output ) ;

        $this->assertStringContainsString( 'Mycmd build' , $fixture->buffer->fetch() ) ;
    }

    public function testStartCommandWithEmptyActionUsesNameOnly(): void
    {
        $fixture = new LifecycleFixture() ;
        $fixture->action = '' ;
        [ $input , $output ] = $this->io() ;

        $fixture->callStart( $input , $output ) ;

        $output = $fixture->buffer->fetch() ;
        $this->assertStringContainsString( 'Mycmd' , $output ) ;
        $this->assertStringNotContainsString( 'Mycmd build' , $output ) ;
    }

    public function testEndCommandReturnsGivenStatus(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $status = $fixture->endCommand( $input , $output , ExitCode::FAILURE ) ;

        $this->assertSame( ExitCode::FAILURE , $status ) ;
    }

    public function testEndCommandRendersEndDateWhenStartDateProvided(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $fixture->endCommand( $input , $output , ExitCode::SUCCESS , 0 , new DateTimeImmutable() ) ;

        $this->assertStringContainsString( 'End:' , $fixture->buffer->fetch() ) ;
    }

    public function testEndCommandSkipsEndDateWhenNoStartDate(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $fixture->endCommand( $input , $output , ExitCode::SUCCESS , 0 , null ) ;

        $this->assertStringNotContainsString( 'End:' , $fixture->buffer->fetch() ) ;
    }

    public function testEndCommandShowsDurationWhenTimestampProvided(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $fixture->endCommand( $input , $output , ExitCode::SUCCESS , microtime( true ) - 1.0 ) ;

        $this->assertStringContainsString( 'Done in' , $fixture->buffer->fetch() ) ;
    }

    public function testEndCommandShowsPlainDoneWhenNoTimestamp(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $fixture->endCommand( $input , $output , ExitCode::SUCCESS , 0 ) ;

        $output = $fixture->buffer->fetch() ;
        $this->assertStringContainsString( 'Done!' , $output ) ;
        $this->assertStringNotContainsString( 'Done in' , $output ) ;
    }

    public function testEndCommandAlwaysRendersClosingMessage(): void
    {
        $fixture = new LifecycleFixture() ;
        [ $input , $output ] = $this->io() ;

        $fixture->endCommand( $input , $output ) ;

        $this->assertStringContainsString( 'Thank you and see you soon!' , $fixture->buffer->fetch() ) ;
    }
}
