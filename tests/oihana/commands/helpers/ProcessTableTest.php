<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\commands\helpers\processTable;

final class ProcessTableTest extends TestCase
{
    public function testReadsThePidTheParentAndTheState(): void
    {
        $table = processTable( "    1     0 Ss\n   42     1 S+\n" ) ;

        $this->assertSame
        (
            [
                1  => [ 'ppid' => 0 , 'state' => 'Ss' ] ,
                42 => [ 'ppid' => 1 , 'state' => 'S+' ] ,
            ] ,
            $table
        ) ;
    }

    public function testALineWithoutAStateGetsAnEmptyOne(): void
    {
        $this->assertSame( [ 7 => [ 'ppid' => 3 , 'state' => '' ] ] , processTable( "7 3\n" ) ) ;
    }

    public function testALineThatDoesNotStartWithTwoNumbersIsSkipped(): void
    {
        $table = processTable( "  PID  PPID STAT\nwarning: something\n  5   1 Z\n\n" ) ;

        $this->assertSame( [ 5 => [ 'ppid' => 1 , 'state' => 'Z' ] ] , $table ) ;
    }

    public function testAnEmptyTextIsAnEmptyTable(): void
    {
        $this->assertSame( [] , processTable( '' ) ) ;
    }

    public function testWithoutTextItReadsThisMachine(): void
    {
        $table = processTable() ;

        $this->assertArrayHasKey( getmypid() , $table ) ;
        $this->assertSame( posix_getppid() , $table[ getmypid() ][ 'ppid' ] ) ;
    }
}
