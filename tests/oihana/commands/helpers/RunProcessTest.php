<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function oihana\commands\helpers\processTable;
use function oihana\commands\helpers\runProcess;

final class RunProcessTest extends TestCase
{
    /**
     * A process that kills the writer if the test blocks, so a regression fails instead of
     * hanging the whole suite.
     *
     * @var resource|null
     */
    private $watchdog = null ;

    protected function tearDown() :void
    {
        if( is_resource( $this->watchdog ) )
        {
            $pid = proc_get_status( $this->watchdog )[ 'pid' ] ;

            @shell_exec( sprintf( 'pkill -P %d 2>/dev/null' , $pid ) ) ;

            proc_terminate( $this->watchdog , 9 ) ;
            proc_close( $this->watchdog ) ;
        }

        $this->watchdog = null ;
    }

    // ------------------------------------------------------------------ outputs

    public function testBothOutputsAndTheStatusAreReturned(): void
    {
        $process = runProcess( 'printf out; printf err >&2; exit 3' ) ;

        $this->assertSame( 'out' , $process->output ) ;
        $this->assertSame( 'err' , $process->error ) ;
        $this->assertSame( 3 , $process->status ) ;
        $this->assertFalse( $process->timedOut ) ;
        $this->assertIsFloat( $process->seconds ) ;
    }

    public function testTheOutputsAreNotTrimmed(): void
    {
        $process = runProcess( 'printf "  out\n"' ) ;

        $this->assertSame( "  out\n" , $process->output ) ;
    }

    public function testALongErrorOutputDoesNotBlock(): void
    {
        // 200 KB on the error output first, then 200 KB on standard output. Read one after the
        // other, the writer waits for its error pipe to be read, and the reader for the end of
        // standard output: neither ever moves.

        $marker  = 'run-process-test-' . bin2hex( random_bytes( 6 ) ) ;
        $writer  = sprintf( '/* %s */ fwrite( STDERR , str_repeat( "e" , 200000 ) ) ; fwrite( STDOUT , str_repeat( "o" , 200000 ) ) ;' , $marker ) ;

        $this->startWatchdog( $marker , 15 ) ;

        $started = microtime( true ) ;
        $process = runProcess( [ PHP_BINARY , '-r' , $writer ] ) ;

        $this->assertLessThan( 15.0 , microtime( true ) - $started , 'The process blocked on its error output.' ) ;
        $this->assertSame( 0 , $process->status ) ;
        $this->assertSame( 200000 , strlen( $process->error ) ) ;
        $this->assertSame( 200000 , strlen( $process->output ) ) ;
    }

    public function testOnOutputReceivesStandardOutputAndNothingIsKept(): void
    {
        $chunks = [] ;

        $process = runProcess
        (
            [ PHP_BINARY , '-r' , 'fwrite( STDOUT , str_repeat( "o" , 300000 ) ) ; fwrite( STDERR , "e" ) ;' ] ,
            onOutput: function( string $chunk ) use ( &$chunks ) { $chunks[] = $chunk ; }
        ) ;

        $this->assertSame( str_repeat( 'o' , 300000 ) , implode( '' , $chunks ) ) ;
        $this->assertSame( '' , $process->output ) ;
        $this->assertSame( 'e' , $process->error ) ;
    }

    // --------------------------------------------------------------- the command

    public function testAnArrayRunsWithoutAShell(): void
    {
        $process = runProcess( [ 'printf' , '%s' , '$HOME; echo no' ] ) ;

        $this->assertSame( '$HOME; echo no' , $process->output ) ;
    }

    public function testTheWorkingDirectoryAndTheEnvironmentAreGiven(): void
    {
        $directory = realpath( sys_get_temp_dir() ) ;

        $process = runProcess
        (
            [ 'sh' , '-c' , 'pwd -P; printf "%s" "$RUN_PROCESS_TEST"' ] ,
            cwd: $directory ,
            env: [ 'RUN_PROCESS_TEST' => 'given' , 'PATH' => (string) getenv( 'PATH' ) ]
        ) ;

        $this->assertSame( $directory . "\ngiven" , $process->output ) ;
    }

    public function testStandardInputIsEmpty(): void
    {
        // A process that asks a question reads an end of file at once.

        $process = runProcess( [ 'sh' , '-c' , 'read answer; echo "read=$?"' ] , timeout: 10 ) ;

        $this->assertFalse( $process->timedOut ) ;
        $this->assertSame( "read=1\n" , $process->output ) ;
    }

    public function testAProcessThatCannotStartRaisesWithoutItsArguments(): void
    {
        try
        {
            runProcess( [ 'ls' , '--password=secret' ] , cwd: '/nonexistent-' . bin2hex( random_bytes( 4 ) ) ) ;
        }
        catch( RuntimeException $exception )
        {
            $this->assertSame( 'Failed to execute the command `ls`.' , $exception->getMessage() ) ;
            return ;
        }

        $this->fail( 'A process that cannot start must raise.' ) ;
    }

    public function testAStringCommandNamesItsProgramOnlyWhenItCannotStart(): void
    {
        try
        {
            runProcess( 'mysql --password=secret' , cwd: '/nonexistent-' . bin2hex( random_bytes( 4 ) ) ) ;
        }
        catch( RuntimeException $exception )
        {
            $this->assertSame( 'Failed to execute the command `mysql`.' , $exception->getMessage() ) ;
            return ;
        }

        $this->fail( 'A process that cannot start must raise.' ) ;
    }

    // ---------------------------------------------------------------- time limit

    public function testTheTimeLimitStopsTheProcess(): void
    {
        $process = runProcess( [ 'sleep' , '30' ] , timeout: 1 ) ;

        $this->assertTrue( $process->timedOut ) ;
        $this->assertSame( 124 , $process->status ) ;
        $this->assertGreaterThanOrEqual( 1.0 , $process->seconds ) ;
        $this->assertLessThan( 5.0 , $process->seconds ) ;
    }

    public function testTheTimeLimitStopsTheChildrenToo(): void
    {
        // The shell prints the pid of its child, then waits for it.

        $process = runProcess( 'sleep 30 & echo $!; wait' , timeout: 1 ) ;

        $child = (int) trim( $process->output ) ;

        $this->assertTrue( $process->timedOut ) ;
        $this->assertGreaterThan( 0 , $child ) ;
        $this->assertFalse( $this->isRunning( $child ) , 'The child of the command was left running.' ) ;
    }

    public function testWhatWasSaidBeforeTheTimeLimitIsKept(): void
    {
        $process = runProcess( 'printf before; printf said >&2; sleep 30' , timeout: 1 ) ;

        $this->assertTrue( $process->timedOut ) ;
        $this->assertSame( 'before' , $process->output ) ;
        $this->assertSame( 'said' , $process->error ) ;
    }

    public function testAProcessThatIgnoresSigtermIsKilledAfterTheGrace(): void
    {
        $process = runProcess( [ 'sh' , '-c' , 'trap "" TERM; sleep 30' ] , timeout: 0.5 , grace: 0.5 ) ;

        $this->assertTrue( $process->timedOut ) ;
        $this->assertLessThan( 5.0 , $process->seconds ) ;
    }

    public function testNoTimeLimitWhenItIsZeroOrNull(): void
    {
        $this->assertFalse( runProcess( [ 'sleep' , '0.2' ] , timeout: 0 )->timedOut ) ;
        $this->assertFalse( runProcess( [ 'sleep' , '0.2' ] )->timedOut ) ;
    }

    public function testAProcessThatEndsInTimeIsNotStopped(): void
    {
        $process = runProcess( 'printf done' , timeout: 10 ) ;

        $this->assertFalse( $process->timedOut ) ;
        $this->assertSame( 0 , $process->status ) ;
        $this->assertSame( 'done' , $process->output ) ;
    }

    // ------------------------------------------------------------------- helpers

    private function isRunning( int $pid ) :bool
    {
        $table = processTable() ;

        return isset( $table[ $pid ] ) && !str_starts_with( $table[ $pid ][ 'state' ] , 'Z' ) ;
    }

    private function startWatchdog( string $marker , int $seconds ) :void
    {
        $this->watchdog = proc_open
        (
            [ 'sh' , '-c' , sprintf( 'sleep %d; pkill -f %s' , $seconds , escapeshellarg( $marker ) ) ] ,
            [ 0 => [ 'file' , '/dev/null' , 'r' ] , 1 => [ 'file' , '/dev/null' , 'w' ] , 2 => [ 'file' , '/dev/null' , 'w' ] ] ,
            $pipes
        ) ;
    }
}
