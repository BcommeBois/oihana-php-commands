<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\commands\helpers\processDescendants;
use function oihana\commands\helpers\processTable;

final class ProcessDescendantsTest extends TestCase
{
    public function testTheDeepestComeFirst(): void
    {
        $table = processTable( "10 1 S\n11 10 S\n12 11 S\n13 10 S\n20 1 S\n" ) ;

        $this->assertSame( [ 12 , 11 , 13 ] , processDescendants( 10 , $table ) ) ;
    }

    public function testAProcessWithoutChildrenHasNone(): void
    {
        $table = processTable( "10 1 S\n11 10 S\n" ) ;

        $this->assertSame( [] , processDescendants( 11 , $table ) ) ;
    }

    public function testAProcessAbsentFromTheTableHasNone(): void
    {
        $this->assertSame( [] , processDescendants( 99 , processTable( "10 1 S\n" ) ) ) ;
    }

    public function testALoopInTheTableEndsTheWalk(): void
    {
        // A table read while processes come and go can be wrong; the walk must still end.

        $table = processTable( "10 1 S\n11 10 S\n12 11 S\n10 12 S\n" ) ;

        $this->assertSame( [ 12 , 11 ] , processDescendants( 10 , $table ) ) ;
    }

    public function testWithoutATableItFindsARealChild(): void
    {
        $process = proc_open( [ 'sleep' , '5' ] , [] , $pipes ) ;

        $this->assertIsResource( $process ) ;

        $pid = proc_get_status( $process )[ 'pid' ] ;

        try
        {
            $this->assertContains( $pid , processDescendants( getmypid() ) ) ;
        }
        finally
        {
            proc_terminate( $process , 9 ) ;
            proc_close( $process ) ;
        }
    }
}
