<?php

declare(strict_types=1);

namespace tests\oihana\commands\styles;

use oihana\commands\styles\OutputStyle;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Concrete subclass used to instantiate the abstract OutputStyle and expose its
 * protected getErrorOutput() method.
 */
class ConcreteOutputStyle extends OutputStyle
{
    public function exposeErrorOutput(): OutputInterface
    {
        return $this->getErrorOutput() ;
    }
}

/**
 * Unit tests for OutputStyle
 */
class OutputStyleTest extends TestCase
{
    public function testGetOutputReturnsConstructorOutput(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $this->assertSame( $output , $style->getOutput() ) ;
    }

    public function testDelegatesGettersToUnderlyingOutput(): void
    {
        $formatter = $this->createStub( OutputFormatterInterface::class ) ;

        $output = $this->createStub( OutputInterface::class ) ;
        $output->method( 'getFormatter'  )->willReturn( $formatter ) ;
        $output->method( 'getVerbosity'  )->willReturn( OutputInterface::VERBOSITY_DEBUG ) ;
        $output->method( 'isDebug'       )->willReturn( true ) ;
        $output->method( 'isDecorated'   )->willReturn( true ) ;
        $output->method( 'isSilent'      )->willReturn( true ) ;
        $output->method( 'isQuiet'       )->willReturn( true ) ;
        $output->method( 'isVerbose'     )->willReturn( true ) ;
        $output->method( 'isVeryVerbose' )->willReturn( true ) ;

        $style = new ConcreteOutputStyle( $output ) ;

        $this->assertSame( $formatter , $style->getFormatter() ) ;
        $this->assertSame( OutputInterface::VERBOSITY_DEBUG , $style->getVerbosity() ) ;
        $this->assertTrue( $style->isDebug() ) ;
        $this->assertTrue( $style->isDecorated() ) ;
        $this->assertTrue( $style->isSilent() ) ;
        $this->assertTrue( $style->isQuiet() ) ;
        $this->assertTrue( $style->isVerbose() ) ;
        $this->assertTrue( $style->isVeryVerbose() ) ;
    }

    public function testWriteDelegatesAndReturnsSelf(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $result = $style->write( 'hello' ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertSame( 'hello' , $output->fetch() ) ;
    }

    public function testWritelnDelegatesAndReturnsSelf(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $result = $style->writeln( 'line' ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertStringContainsString( 'line' , $output->fetch() ) ;
    }

    public function testNewLineWritesRequestedNumberOfLines(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $result = $style->newLine( 3 ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertSame( str_repeat( PHP_EOL , 3 ) , $output->fetch() ) ;
    }

    public function testSetDecoratedReturnsSelf(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $result = $style->setDecorated( true ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertTrue( $output->isDecorated() ) ;
    }

    public function testSetVerbosityReturnsSelf(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $result = $style->setVerbosity( OutputInterface::VERBOSITY_VERBOSE ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertSame( OutputInterface::VERBOSITY_VERBOSE , $output->getVerbosity() ) ;
    }

    public function testSetFormatterReturnsSelf(): void
    {
        $output    = new BufferedOutput() ;
        $style     = new ConcreteOutputStyle( $output ) ;
        $formatter = new OutputFormatter() ;

        $result = $style->setFormatter( $formatter ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertSame( $formatter , $output->getFormatter() ) ;
    }

    public function testGetErrorOutputReturnsMainOutputWhenNotConsoleOutput(): void
    {
        $output = new BufferedOutput() ;
        $style  = new ConcreteOutputStyle( $output ) ;

        $this->assertSame( $output , $style->exposeErrorOutput() ) ;
    }

    public function testGetErrorOutputReturnsErrorStreamForConsoleOutput(): void
    {
        $errorOutput = $this->createStub( OutputInterface::class ) ;

        $output = $this->createStub( ConsoleOutputInterface::class ) ;
        $output->method( 'getErrorOutput' )->willReturn( $errorOutput ) ;

        $style = new ConcreteOutputStyle( $output ) ;

        $this->assertSame( $errorOutput , $style->exposeErrorOutput() ) ;
    }
}
