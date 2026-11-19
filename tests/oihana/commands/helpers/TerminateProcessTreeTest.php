<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\commands\helpers\processDescendants;
use function oihana\commands\helpers\processTable;
use function oihana\commands\helpers\terminateProcessTree;

final class TerminateProcessTreeTest extends TestCase
{
    public function testTheProcessAndAllItsDescendantsAreStopped(): void
    {
        [ $process , $pid , $tree ] = $this->startTree( 'sleep 30 & sleep 30 & wait' , 2 ) ;

        $left = terminateProcessTree( $pid , 2.0 ) ;

        proc_close( $process ) ;

        $this->assertSame( [] , $left ) ;
        $this->assertSame( [] , $this->running( $tree ) ) ;
    }

    public function testAProcessThatIgnoresSigtermIsKilled(): void
    {
        // An ignored signal stays ignored across exec: sleep ignores SIGTERM too.

        [ $process , $pid , $tree ] = $this->startTree( 'trap "" TERM; sleep 30 & wait' , 1 ) ;

        $started = microtime( true ) ;

        $left = terminateProcessTree( $pid , 0.5 ) ;

        proc_close( $process ) ;

        $this->assertSame( [] , $left ) ;
        $this->assertSame( [] , $this->running( $tree ) ) ;
        $this->assertLessThan( 5.0 , microtime( true ) - $started ) ;
    }

    public function testAProcessThatStopsOnSigtermIsNotWaitedFor(): void
    {
        [ $process , $pid ] = $this->startTree( 'sleep 30 & wait' , 1 ) ;

        $started = microtime( true ) ;

        terminateProcessTree( $pid , 10.0 ) ;

        proc_close( $process ) ;

        $this->assertLessThan( 5.0 , microtime( true ) - $started , 'The grace period was waited out for nothing.' ) ;
    }

    public function testAProcessAlreadyGoneIsNothingToStop(): void
    {
        $process = proc_open( [ 'true' ] , [] , $pipes ) ;
        $pid     = proc_get_status( $process )[ 'pid' ] ;

        proc_close( $process ) ;

        $this->assertSame( [] , terminateProcessTree( $pid , 0.1 ) ) ;
    }

    /**
     * Starts `sh -c <script>` and waits until it has the given number of children.
     *
     * @return array{0: resource, 1: int, 2: array<int>}
     */
    private function startTree( string $script , int $children ) :array
    {
        $process = proc_open( [ 'sh' , '-c' , $script ] , [] , $pipes ) ;

        $this->assertIsResource( $process ) ;

        $pid      = proc_get_status( $process )[ 'pid' ] ;
        $deadline = microtime( true ) + 5.0 ;

        do
        {
            $tree = processDescendants( $pid ) ;
            usleep( 20_000 ) ;
        }
        while( count( $tree ) < $children && microtime( true ) < $deadline ) ;

        $this->assertCount( $children , $tree , 'The tree did not start.' ) ;

        return [ $process , $pid , [ ...$tree , $pid ] ] ;
    }

    /**
     * @param array<int> $pids
     * @return array<int> Those still running, zombies aside.
     */
    private function running( array $pids ) :array
    {
        $table = processTable() ;

        return array_values( array_filter( $pids , fn( int $pid ) => isset( $table[ $pid ] ) && !str_starts_with( $table[ $pid ][ 'state' ] , 'Z' ) ) ) ;
    }
}
