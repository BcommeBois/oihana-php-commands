<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\traits\ConsoleLoggerTrait;
use oihana\logging\CompositeLogger;

use PHPUnit\Framework\TestCase;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Consumer of ConsoleLoggerTrait exposing the protected logger property and helpers.
 */
class ConsoleLoggerFixture
{
    use ConsoleLoggerTrait;

    public function setRawLogger( ?LoggerInterface $logger ): void
    {
        $this->logger = $logger ;
    }

    public function getRawLogger(): ?LoggerInterface
    {
        return $this->logger ;
    }

    public function comp(): CompositeLogger
    {
        return $this->compositeLogger() ;
    }
}

/**
 * Unit tests for ConsoleLoggerTrait
 */
class ConsoleLoggerTraitTest extends TestCase
{
    private function fixture(): ConsoleLoggerFixture
    {
        $fixture = new ConsoleLoggerFixture() ;
        $fixture->setRawLogger( null ) ;
        $fixture->console = null ; // initialise the property declared without a default
        return $fixture ;
    }

    public function testAddLoggerWrapsIntoCompositeAndRegisters(): void
    {
        $fixture = $this->fixture() ;
        $logger  = $this->createStub( LoggerInterface::class ) ;

        $result = $fixture->addLogger( $logger ) ;

        $this->assertSame( $fixture , $result ) ;
        $this->assertInstanceOf( CompositeLogger::class , $fixture->getRawLogger() ) ;
        $this->assertTrue( $fixture->hasLogger( $logger ) ) ;
    }

    public function testCompositeLoggerWrapsExistingLogger(): void
    {
        $fixture  = $this->fixture() ;
        $existing = $this->createStub( LoggerInterface::class ) ;
        $fixture->setRawLogger( $existing ) ;

        $composite = $fixture->comp() ;

        $this->assertInstanceOf( CompositeLogger::class , $composite ) ;
        $this->assertTrue( $composite->hasLogger( $existing ) ) ;
    }

    public function testCompositeLoggerReturnsSameInstanceWhenAlreadyComposite(): void
    {
        $fixture = $this->fixture() ;

        $first  = $fixture->comp() ;
        $second = $fixture->comp() ;

        $this->assertSame( $first , $second ) ;
    }

    public function testCompositeLoggerWithNullLoggerCreatesEmptyComposite(): void
    {
        $fixture = $this->fixture() ;

        $composite = $fixture->comp() ;

        $this->assertInstanceOf( CompositeLogger::class , $composite ) ;
        $this->assertSame( [] , $composite->getLoggers() ) ;
    }

    public function testHasLoggerReturnsFalseWhenLoggerNotComposite(): void
    {
        $fixture = $this->fixture() ;
        $fixture->setRawLogger( $this->createStub( LoggerInterface::class ) ) ;

        $this->assertFalse( $fixture->hasLogger( $this->createStub( LoggerInterface::class ) ) ) ;
    }

    public function testHasLoggerReturnsFalseWhenNotRegistered(): void
    {
        $fixture = $this->fixture() ;
        $fixture->addLogger( $this->createStub( LoggerInterface::class ) ) ;

        $this->assertFalse( $fixture->hasLogger( $this->createStub( LoggerInterface::class ) ) ) ;
    }

    public function testInitializeConsoleLoggerCreatesAndRegisters(): void
    {
        $fixture = $this->fixture() ;

        $result = $fixture->initializeConsoleLogger( new BufferedOutput() ) ;

        $this->assertSame( $fixture , $result ) ;
        $this->assertInstanceOf( ConsoleLogger::class , $fixture->console ) ;
        $this->assertTrue( $fixture->hasLogger( $fixture->console ) ) ;
    }

    public function testInitializeConsoleLoggerReplacesPreviousConsole(): void
    {
        $fixture = $this->fixture() ;

        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;
        $previous = $fixture->console ;

        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;

        $this->assertNotSame( $previous , $fixture->console ) ;
        $this->assertTrue( $fixture->hasLogger( $fixture->console ) ) ;
        $this->assertFalse( $fixture->hasLogger( $previous ) ) ;
    }

    public function testInitializeConsoleLoggerWithNullOutputClearsConsole(): void
    {
        $fixture = $this->fixture() ;

        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;
        $previous = $fixture->console ;

        $fixture->initializeConsoleLogger( null ) ;

        $this->assertNull( $fixture->console ) ;
        $this->assertFalse( $fixture->hasLogger( $previous ) ) ;
    }

    public function testRemoveLoggerLeavesConsoleUntouchedForOtherLoggers(): void
    {
        $fixture = $this->fixture() ;
        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;

        $other = $this->createStub( LoggerInterface::class ) ;
        $fixture->addLogger( $other ) ;

        $result = $fixture->removeLogger( $other ) ;

        $this->assertSame( $fixture , $result ) ;
        $this->assertFalse( $fixture->hasLogger( $other ) ) ;
        $this->assertInstanceOf( ConsoleLogger::class , $fixture->console ) ;
    }

    public function testRemoveLoggerClearsConsoleWhenRemovingConsole(): void
    {
        $fixture = $this->fixture() ;
        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;
        $console = $fixture->console ;

        $fixture->removeLogger( $console ) ;

        $this->assertNull( $fixture->console ) ;
        $this->assertFalse( $fixture->hasLogger( $console ) ) ;
    }

    public function testRemoveLoggerWhenLoggerNotComposite(): void
    {
        $fixture = $this->fixture() ; // logger is null (not a composite)

        $result = $fixture->removeLogger( $this->createStub( LoggerInterface::class ) ) ;

        $this->assertSame( $fixture , $result ) ;
    }

    public function testClearLoggerRemovesAllAndResetsConsole(): void
    {
        $fixture = $this->fixture() ;
        $fixture->initializeConsoleLogger( new BufferedOutput() ) ;
        $fixture->addLogger( $this->createStub( LoggerInterface::class ) ) ;

        $result = $fixture->clearLogger() ;

        $this->assertSame( $fixture , $result ) ;
        $this->assertNull( $fixture->console ) ;
        $this->assertSame( [] , $fixture->comp()->getLoggers() ) ;
    }
}
