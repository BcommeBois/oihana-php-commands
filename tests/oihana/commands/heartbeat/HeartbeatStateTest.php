<?php

declare(strict_types=1);

namespace tests\oihana\commands\heartbeat;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use oihana\commands\heartbeat\HeartbeatState;

/**
 * What the watcher remembers of a failure : its first date kept, the alert every
 * hour at most, and a file that cannot be read never breaking the pass.
 */
#[CoversClass( HeartbeatState::class )]
final class HeartbeatStateTest extends TestCase
{
    private string $file = '' ;

    protected function setUp() :void
    {
        $this->file = sys_get_temp_dir() . '/heartbeat-state-' . bin2hex( random_bytes( 6 ) ) . '.json' ;
    }

    protected function tearDown() :void
    {
        if( is_file( $this->file ) )
        {
            unlink( $this->file ) ;
        }
    }

    public function testNoFileIsAHealthyState() :void
    {
        $state = HeartbeatState::read( $this->file ) ;

        $this->assertFalse( $state->isFailing() ) ;
        $this->assertNull( $state->since ) ;
        $this->assertNull( $state->alerted ) ;
        $this->assertSame( '' , $state->error ) ;
    }

    public function testAFileThatIsNotJsonIsAHealthyState() :void
    {
        file_put_contents( $this->file , 'not json' ) ;

        $this->assertFalse( HeartbeatState::read( $this->file )->isFailing() ) ;
    }

    public function testValuesOfTheWrongTypeAreIgnored() :void
    {
        file_put_contents( $this->file , '{"since":"yesterday","alerted":12.5,"error":42}' ) ;

        $state = HeartbeatState::read( $this->file ) ;

        $this->assertNull( $state->since ) ;
        $this->assertNull( $state->alerted ) ;
        $this->assertSame( '' , $state->error ) ;
    }

    public function testAWrittenStateIsReadBack() :void
    {
        $this->assertTrue( new HeartbeatState( since : 100 , alerted : 160 , error : 'Timed out — 10 s ⏱' )->write( $this->file ) ) ;

        $state = HeartbeatState::read( $this->file ) ;

        $this->assertSame( 100 , $state->since ) ;
        $this->assertSame( 160 , $state->alerted ) ;
        $this->assertSame( 'Timed out — 10 s ⏱' , $state->error ) ;
        $this->assertStringContainsString( 'Timed out — 10 s ⏱' , (string) file_get_contents( $this->file ) ) ;
    }

    public function testAFailureKeepsItsFirstDate() :void
    {
        $first  = new HeartbeatState()->fail( 100 , 'timeout' ) ;
        $second = $first->fail( 400 , 'refused' ) ;

        $this->assertSame( 100 , $first->since ) ;
        $this->assertSame( 100 , $second->since ) ;
        $this->assertSame( 'refused' , $second->error ) ;
        $this->assertNull( $second->alerted ) ;
        $this->assertTrue( $second->isFailing() ) ;
    }

    public function testAnAlertKeepsTheFailureAndItsError() :void
    {
        $state = new HeartbeatState()->fail( 100 , 'timeout' )->alert( 100 )->fail( 400 , 'refused' )->alert( 3800 ) ;

        $this->assertSame( 100  , $state->since ) ;
        $this->assertSame( 3800 , $state->alerted ) ;
        $this->assertSame( 'refused' , $state->error ) ;
    }

    public function testAnAlertWithoutAFailureStartsIt() :void
    {
        $state = new HeartbeatState()->alert( 500 ) ;

        $this->assertSame( 500 , $state->since ) ;
        $this->assertSame( 500 , $state->alerted ) ;
    }

    public function testTheFirstFailureIsAlwaysDue() :void
    {
        $this->assertTrue( new HeartbeatState()->fail( 100 , 'timeout' )->isAlertDue( 100 , 3600 ) ) ;
    }

    public function testTheNextAlertIsDueOnlyAfterTheInterval() :void
    {
        $state = new HeartbeatState( since : 100 , alerted : 100 ) ;

        $this->assertFalse( $state->isAlertDue( 400  , 3600 ) ) ;
        $this->assertFalse( $state->isAlertDue( 3699 , 3600 ) ) ;
        $this->assertTrue ( $state->isAlertDue( 3700 , 3600 ) ) ;
        $this->assertTrue ( $state->isAlertDue( 9000 , 3600 ) ) ;
    }

    public function testClearRemovesTheFile() :void
    {
        new HeartbeatState( since : 100 )->write( $this->file ) ;

        $this->assertTrue( HeartbeatState::clear( $this->file ) ) ;
        $this->assertFileDoesNotExist( $this->file ) ;
        $this->assertTrue( HeartbeatState::clear( $this->file ) ) ;
    }

    public function testAStateThatCannotBeWrittenSaysSo() :void
    {
        $this->assertFalse( new HeartbeatState( since : 1 )->write( sys_get_temp_dir() . '/no-such-dir-' . bin2hex( random_bytes( 4 ) ) . '/state.json' ) ) ;
    }
}
