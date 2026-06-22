<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\traits\IOTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Unit tests for IOTrait
 */
class IOTraitTest extends TestCase
{
    private object $trait ;

    protected function setUp(): void
    {
        $this->trait = new class { use IOTrait; } ;
    }

    private function input(): InputInterface
    {
        return new ArrayInput( [] ) ;
    }

    private function style( ?BufferedOutput $output = null ): SymfonyStyle
    {
        return new SymfonyStyle( $this->input() , $output ?? new BufferedOutput() ) ;
    }

    // ---------------------------------------------------------------- getIO

    public function testGetIOCreatesAndCachesStyle(): void
    {
        $first  = $this->trait->getIO( $this->input() , new BufferedOutput() ) ;
        $second = $this->trait->getIO( $this->input() , new BufferedOutput() ) ;

        $this->assertInstanceOf( SymfonyStyle::class , $first ) ;
        $this->assertSame( $first , $second ) ;
    }

    public function testGetIOReturnsExistingWhenAlreadySet(): void
    {
        $existing = $this->style() ;
        $this->trait->io = $existing ;

        $this->assertSame( $existing , $this->trait->getIO( $this->input() , new BufferedOutput() ) ) ;
    }

    // ------------------------------------------------------------- runIOAction

    public function testRunIOActionInvokesCallbackWithIO(): void
    {
        $io       = $this->style() ;
        $received = null ;

        $result = $this->trait->runIOAction( function( $arg ) use ( &$received ) { $received = $arg ; return 'R' ; } , $io ) ;

        $this->assertSame( 'R' , $result ) ;
        $this->assertSame( $io , $received ) ;
    }

    public function testRunIOActionRendersTitleAndFinish(): void
    {
        $output = new BufferedOutput() ;
        $io     = $this->style( $output ) ;

        $this->trait->runIOAction( fn() => null , $io , 'My Title' , 'Done message' ) ;

        $text = $output->fetch() ;
        $this->assertStringContainsString( 'My Title' , $text ) ;
        $this->assertStringContainsString( 'Done message' , $text ) ;
    }

    public function testRunIOActionWithoutTitleOrFinishRendersNothing(): void
    {
        $output = new BufferedOutput() ;
        $io     = $this->style( $output ) ;

        $result = $this->trait->runIOAction( fn() => 'value' , $io , '' , '' ) ;

        $this->assertSame( 'value' , $result ) ;
        $this->assertSame( '' , $output->fetch() ) ;
    }

    public function testRunIOActionWithNullIOSkipsRendering(): void
    {
        $result = $this->trait->runIOAction( fn( $io ) => 'X' , null , 'title' , 'finish' ) ;

        $this->assertSame( 'X' , $result ) ;
    }

    // -------------------------------------------------------------- runAction

    public function testRunActionWithoutInputReturnsCallbackResult(): void
    {
        $result = $this->trait->runAction( fn() => 'direct' , null , new BufferedOutput() ) ;
        $this->assertSame( 'direct' , $result ) ;
    }

    public function testRunActionWithoutOutputReturnsCallbackResult(): void
    {
        $result = $this->trait->runAction( fn() => 'direct2' , $this->input() , null ) ;
        $this->assertSame( 'direct2' , $result ) ;
    }

    public function testRunActionUsesDefaultGetIO(): void
    {
        $output = new BufferedOutput() ;

        $result = $this->trait->runAction( fn( $io ) => 'ok' , $this->input() , $output , 'Section Title' ) ;

        $this->assertSame( 'ok' , $result ) ;
        $this->assertStringContainsString( 'Section Title' , $output->fetch() ) ;
    }

    public function testRunActionUsesCustomGetIO(): void
    {
        $customOutput = new BufferedOutput() ;
        $getIO        = fn( $input , $output ) => new SymfonyStyle( $input , $customOutput ) ;

        $result = $this->trait->runAction( fn( $io ) => 'ok' , $this->input() , new BufferedOutput() , 'Custom Title' , '' , 1 , $getIO ) ;

        $this->assertSame( 'ok' , $result ) ;
        $this->assertStringContainsString( 'Custom Title' , $customOutput->fetch() ) ;
    }

    // ------------------------------------------------------------ batchIOActions

    public function testBatchIOActionsRunsEachCallback(): void
    {
        $calls = [] ;

        $this->trait->batchIOActions
        ([
            [ 'callback' => function() use ( &$calls ) { $calls[] = 'a' ; } ] ,
            [ 'callback' => function() use ( &$calls ) { $calls[] = 'b' ; } ] ,
        ] , $this->style() ) ;

        $this->assertSame( [ 'a' , 'b' ] , $calls ) ;
    }

    public function testBatchIOActionsSkipsActionsWithoutCallback(): void
    {
        $calls = [] ;

        $this->trait->batchIOActions
        ([
            [ 'title' => 'no callback here' ] ,
            [ 'callback' => function() use ( &$calls ) { $calls[] = 'ran' ; } ] ,
        ] , $this->style() ) ;

        $this->assertSame( [ 'ran' ] , $calls ) ;
    }

    public function testBatchIOActionsAppliesNumbering(): void
    {
        $output = new BufferedOutput() ;
        $io     = $this->style( $output ) ;

        $this->trait->batchIOActions
        ([
            [ 'callback' => fn() => null , 'title' => 'First step'  ] ,
            [ 'callback' => fn() => null , 'title' => 'Second step' ] ,
        ] , $io , true ) ;

        $text = $output->fetch() ;
        $this->assertStringContainsString( '01. First step'  , $text ) ;
        $this->assertStringContainsString( '02. Second step' , $text ) ;
    }

    public function testBatchIOActionsNumberingSkipsActionsWithoutTitle(): void
    {
        $output = new BufferedOutput() ;
        $io     = $this->style( $output ) ;

        $this->trait->batchIOActions
        ([
            [ 'callback' => fn() => null ] ,                          // no title -> skipped by numbering
            [ 'callback' => fn() => null , 'title' => 'Titled step' ] ,
        ] , $io , true ) ;

        // The counter is only incremented for titled actions, so the first titled one is 01.
        $this->assertStringContainsString( '01. Titled step' , $output->fetch() ) ;
    }

    public function testBatchIOActionsNumberingWithEmptyTitleUsesPrefixOnly(): void
    {
        $output = new BufferedOutput() ;
        $io     = $this->style( $output ) ;

        $this->trait->batchIOActions
        ([
            [ 'callback' => fn() => null , 'title' => '' ] ,
        ] , $io , true ) ;

        $text = $output->fetch() ;
        $this->assertStringContainsString( '01' , $text ) ;
    }
}
