<?php

declare(strict_types=1);

namespace tests\oihana\commands;

use DI\Container;

use oihana\commands\enums\CommandParam;
use oihana\commands\Kernel;

use PHPUnit\Framework\TestCase;

use UnexpectedValueException;

/**
 * Unit tests for Kernel::assertActions(), initializeDescription() and initializeHelp().
 */
class KernelTest extends TestCase
{
    private function kernel( array $init = [] ): Kernel
    {
        return new Kernel( 'test:kernel' , new Container() , $init ) ;
    }

    public function testAssertActionsNoOpWhenActionsNull(): void
    {
        $kernel = $this->kernel() ;
        $kernel->actions = null ;

        $kernel->assertActions() ; // must not throw

        $this->assertNull( $kernel->actions ) ;
    }

    public function testAssertActionsNoOpWhenActionsEmpty(): void
    {
        $kernel = $this->kernel() ;
        $kernel->actions = [] ;

        $kernel->assertActions() ; // must not throw

        $this->assertSame( [] , $kernel->actions ) ;
    }

    public function testAssertActionsPassesWhenActionAllowed(): void
    {
        $kernel = $this->kernel() ;
        $kernel->actions = [ 'build' , 'deploy' ] ;
        $kernel->action  = 'build' ;

        $kernel->assertActions() ; // must not throw

        $this->assertContains( 'build' , $kernel->actions ) ;
    }

    public function testAssertActionsThrowsWhenActionNotAllowed(): void
    {
        $kernel = $this->kernel() ;
        $kernel->actions = [ 'build' ] ;
        $kernel->action  = 'release' ;

        $this->expectException( UnexpectedValueException::class ) ;
        $this->expectExceptionMessage( 'The action "release" is not allowed' ) ;

        $kernel->assertActions() ;
    }

    public function testInitializeDescriptionSetsValue(): void
    {
        $kernel = $this->kernel() ;

        $result = $kernel->initializeDescription( [ CommandParam::DESCRIPTION => 'My description' ] ) ;

        $this->assertSame( $kernel , $result ) ;
        $this->assertSame( 'My description' , $kernel->getDescription() ) ;
    }

    public function testInitializeDescriptionIgnoresEmptyValue(): void
    {
        $kernel = $this->kernel() ;
        $kernel->setDescription( 'untouched' ) ;

        $kernel->initializeDescription( [ CommandParam::DESCRIPTION => '' ] ) ;

        $this->assertSame( 'untouched' , $kernel->getDescription() ) ;
    }

    public function testInitializeHelpSetsValue(): void
    {
        $kernel = $this->kernel() ;

        $result = $kernel->initializeHelp( [ CommandParam::HELP => 'Some help text' ] ) ;

        $this->assertSame( $kernel , $result ) ;
        $this->assertSame( 'Some help text' , $kernel->getHelp() ) ;
    }

    public function testInitializeHelpIgnoresEmptyValue(): void
    {
        $kernel = $this->kernel() ;
        $kernel->setHelp( 'untouched help' ) ;

        $kernel->initializeHelp( [ CommandParam::HELP => '' ] ) ;

        $this->assertSame( 'untouched help' , $kernel->getHelp() ) ;
    }
}
