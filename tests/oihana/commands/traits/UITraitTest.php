<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\traits\UITrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Unit tests for UITrait
 */
class UITraitTest extends TestCase
{
    private object $trait ;

    protected function setUp(): void
    {
        $this->trait = new class { use UITrait; } ;
    }

    public function testCreateProgressBarConfiguresMaxAndWidth(): void
    {
        $bar = $this->trait->createProgressBar( new BufferedOutput() , 10 , 'pretty' , 30 ) ;

        $this->assertInstanceOf( ProgressBar::class , $bar ) ;
        $this->assertSame( 10 , $bar->getMaxSteps() ) ;
        $this->assertSame( 30 , $bar->getBarWidth() ) ;
    }

    public function testCreateProgressBarFromIO(): void
    {
        $io = new SymfonyStyle( new ArrayInput( [] ) , new BufferedOutput() ) ;

        $bar = $this->trait->createProgressBarFromIO( $io , 5 , 'pretty' , 20 ) ;

        $this->assertInstanceOf( ProgressBar::class , $bar ) ;
        $this->assertSame( 5 , $bar->getMaxSteps() ) ;
        $this->assertSame( 20 , $bar->getBarWidth() ) ;
    }

    public function testInitializeProgressBarReturnsSelfAndAppliesDefaultWidth(): void
    {
        $bar = new ProgressBar( new BufferedOutput() ) ;

        $result = $this->trait->initializeProgressBar( $bar ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertSame( 50 , $bar->getBarWidth() ) ;
    }

    public function testInitializeProgressBarWithCustomFormatDefinitions(): void
    {
        $output = new BufferedOutput() ;
        $bar    = new ProgressBar( $output , 4 ) ;

        $this->trait->initializeProgressBar( $bar , 'pretty' , 50 , [ 'pretty' => 'CUSTOM %current%' ] ) ;

        $bar->start() ;

        $this->assertStringContainsString( 'CUSTOM' , $output->fetch() ) ;
    }

    public function testIteratorYieldsAllItemsAndAdvances(): void
    {
        $bar   = $this->trait->createProgressBar( new BufferedOutput() , 3 ) ;
        $items = [ 'a' , 'b' , 'c' ] ;

        $collected = iterator_to_array( $this->trait->progressBarIterator( $bar , $items ) ) ;

        $this->assertSame( $items , $collected ) ;
        $this->assertSame( 3 , $bar->getProgress() ) ;
    }

    public function testIteratorWithStringMessage(): void
    {
        $bar = $this->trait->createProgressBar( new BufferedOutput() , 2 ) ;

        $collected = iterator_to_array( $this->trait->progressBarIterator( $bar , [ 1 , 2 ] , 'processing' ) ) ;

        $this->assertSame( [ 1 , 2 ] , $collected ) ;
    }

    public function testIteratorWithCallableMessage(): void
    {
        $output = new BufferedOutput() ;
        $bar    = $this->trait->createProgressBar( $output , 2 ) ;

        $collected = iterator_to_array
        (
            $this->trait->progressBarIterator( $bar , [ 'x' , 'y' ] , fn( $item ) => "item-$item" )
        );

        $this->assertSame( [ 'x' , 'y' ] , $collected ) ;
    }

    public function testIteratorWithStartAndFinishMessages(): void
    {
        $bar = $this->trait->createProgressBar( new BufferedOutput() , 2 ) ;

        $collected = iterator_to_array
        (
            $this->trait->progressBarIterator( $bar , [ 1 , 2 ] , null , 'starting' , 'finished' )
        );

        $this->assertSame( [ 1 , 2 ] , $collected ) ;
        $this->assertSame( 2 , $bar->getProgress() ) ;
    }

    public function testIteratorWithNullProgressBar(): void
    {
        $collected = iterator_to_array
        (
            $this->trait->progressBarIterator( null , [ 10 , 20 ] , 'msg' , 'start' , 'finish' )
        );

        $this->assertSame( [ 10 , 20 ] , $collected ) ;
    }
}
