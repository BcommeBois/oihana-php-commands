<?php

declare(strict_types=1);

namespace oihana\commands\traits
{
    /**
     * Shadow the global process functions for code in the oihana\commands\traits namespace.
     * Only CommandTrait calls these unqualified, so the shadows are scoped to its tests.
     * proc() runs through runProcess(), in another namespace: its tests run real processes.
     * (SudoTraitTest shadows exec(); these are different symbols, no collision.)
     */
    function shell_exec( string $command )
    {
        \tests\oihana\commands\traits\CommandTraitMock::$commands[] = $command ;
        return \tests\oihana\commands\traits\CommandTraitMock::$shellOutput ;
    }

    function system( string $command , &$result_code = null )
    {
        \tests\oihana\commands\traits\CommandTraitMock::$commands[] = $command ;
        $result_code = \tests\oihana\commands\traits\CommandTraitMock::$systemStatus ;
        return '' ;
    }
}

namespace tests\oihana\commands\traits
{
    use oihana\commands\enums\ExitCode;
    use oihana\commands\Process;
    use oihana\commands\traits\CommandTrait;

    use PHPUnit\Framework\TestCase;

    use RuntimeException;

    /**
     * Registry driving the shadowed process functions.
     */
    final class CommandTraitMock
    {
        public static array   $commands     = [] ;
        public static ?string $shellOutput  = '' ;
        public static int     $systemStatus = 0 ;

        public static function reset(): void
        {
            self::$commands      = [] ;
            self::$shellOutput   = '' ;
            self::$systemStatus  = 0 ;
        }

        public static function lastCommand(): ?string
        {
            return self::$commands[ array_key_last( self::$commands ) ] ?? null ;
        }
    }

    /**
     * Unit tests for CommandTrait (exec / proc / system / initializeCommandOptions).
     */
    class CommandTraitTest extends TestCase
    {
        private object $trait ;

        protected function setUp(): void
        {
            CommandTraitMock::reset() ;

            $this->trait = new class
            {
                use CommandTrait;

                public array $infos = [] ;

                public function info( $message = '' ): void
                {
                    $this->infos[] = (string) $message ;
                }
            } ;
        }

        // ------------------------------------------------ initializeCommandOptions

        public function testInitializeCommandOptionsCreatesOptions(): void
        {
            $invoke = fn( array $init ) => $this->initializeCommandOptions( $init ) ;
            $result = $invoke->call( $this->trait , [ 'sudo' => true ] ) ;

            $this->assertSame( $this->trait , $result ) ;
            $this->assertInstanceOf( \oihana\commands\options\CommandOptions::class , $this->trait->commandOptions ) ;
        }

        // ------------------------------------------------------------------- exec

        public function testExecReturnsTrimmedOutput(): void
        {
            CommandTraitMock::$shellOutput = "  hello world  \n" ;

            $this->assertSame( 'hello world' , $this->trait->exec( 'echo' , 'hello' ) ) ;
        }

        public function testExecVerboseLogsCommand(): void
        {
            CommandTraitMock::$shellOutput = 'ok' ;

            $this->trait->exec( 'ls' , null , null , false , true ) ;

            $this->assertStringContainsString( '[▶] exec:' , implode( "\n" , $this->trait->infos ) ) ;
        }

        public function testExecSilentAppendsStderrRedirect(): void
        {
            CommandTraitMock::$shellOutput = 'ok' ;

            $this->trait->exec( 'ls' , null , null , true ) ;

            $this->assertStringContainsString( '2>&1' , CommandTraitMock::lastCommand() ) ;
        }

        public function testExecWithSudoUsesSudoOptions(): void
        {
            CommandTraitMock::$shellOutput = 'done' ;

            $result = $this->trait->exec( 'whoami' , null , null , false , false , null , null , true ) ;

            $this->assertSame( 'done' , $result ) ;
            $this->assertStringContainsString( 'sudo' , CommandTraitMock::lastCommand() ) ;
        }

        public function testExecThrowsWhenNoOutput(): void
        {
            CommandTraitMock::$shellOutput = null ;

            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'returned no output' ) ;

            $this->trait->exec( 'ls' ) ;
        }

        // ------------------------------------------------------------------- proc

        public function testProcDryRunReturnsNoOpProcess(): void
        {
            $process = $this->trait->proc( 'ls' , null , null , false , null , null , false , true ) ;

            $this->assertInstanceOf( Process::class , $process ) ;
            $this->assertSame( '<do nothing>' , $process->output ) ;
            $this->assertSame( ExitCode::SUCCESS , $process->status ) ;
            $this->assertSame( [] , CommandTraitMock::$commands ) ; // proc_open never called
        }

        public function testProcReturnsProcessWithCapturedStreams(): void
        {
            $process = $this->trait->proc( 'printf "  out  "; printf "  err  " >&2; exit 3' ) ;

            $this->assertSame( 'out' , $process->output ) ;
            $this->assertSame( 'err' , $process->error ) ;
            $this->assertSame( 3 , $process->status ) ;
            $this->assertFalse( $process->timedOut ) ;
        }

        public function testProcVerboseLogsCommand(): void
        {
            $this->trait->proc( 'true' , null , null , true ) ;

            $this->assertStringContainsString( '[▶] proc:' , implode( "\n" , $this->trait->infos ) ) ;
        }

        public function testProcWithSudoUsesSudoOptions(): void
        {
            // Dry run: the command is shown, never run, so nothing asks for a password.

            $this->trait->proc( 'whoami' , null , null , true , null , null , true , true ) ;

            $this->assertStringContainsString( '[▶] proc: sudo' , implode( "\n" , $this->trait->infos ) ) ;
        }

        public function testProcReadsALongErrorOutputWithoutBlocking(): void
        {
            // 200 KB on the error output, then 200 KB on standard output: read one after the
            // other, the process waits for its error pipe to be read, and proc() waits for the
            // end of standard output.

            // A watchdog kills the writer after 15 s, so a regression fails instead of hanging.

            $marker = 'proc-test-' . bin2hex( random_bytes( 6 ) ) ;

            $command = sprintf
            (
                '%s -r %s' ,
                escapeshellarg( PHP_BINARY ) ,
                escapeshellarg( sprintf( '/* %s */ fwrite( STDERR , str_repeat( "e" , 200000 ) ) ; fwrite( STDOUT , str_repeat( "o" , 200000 ) ) ;' , $marker ) )
            ) ;

            $watchdog = proc_open
            (
                [ 'sh' , '-c' , sprintf( 'sleep 15; pkill -f %s' , escapeshellarg( $marker ) ) ] ,
                [ 0 => [ 'file' , '/dev/null' , 'r' ] , 1 => [ 'file' , '/dev/null' , 'w' ] , 2 => [ 'file' , '/dev/null' , 'w' ] ] ,
                $pipes
            ) ;

            try
            {
                $started = microtime( true ) ;
                $process = $this->trait->proc( $command , timeout: 20 ) ;
            }
            finally
            {
                @\shell_exec( sprintf( 'pkill -P %d 2>/dev/null' , proc_get_status( $watchdog )[ 'pid' ] ) ) ;
                proc_terminate( $watchdog , 9 ) ;
                proc_close( $watchdog ) ;
            }

            $this->assertLessThan( 15.0 , microtime( true ) - $started , 'proc() blocked on a long error output.' ) ;
            $this->assertFalse( $process->timedOut , 'proc() blocked on a long error output.' ) ;
            $this->assertSame( 200000 , strlen( $process->error ) ) ;
            $this->assertSame( 200000 , strlen( $process->output ) ) ;
        }

        public function testProcStopsTheCommandAtItsTimeLimit(): void
        {
            $process = $this->trait->proc( 'sleep 30' , timeout: 1 ) ;

            $this->assertTrue( $process->timedOut ) ;
            $this->assertSame( 124 , $process->status ) ;
            $this->assertLessThan( 10 , $process->seconds ) ;
        }

        // ----------------------------------------------------------------- system

        public function testSystemDryRunReturnsSuccessWithoutExecuting(): void
        {
            $status = $this->trait->system( 'rm -rf /tmp/x' , null , null , false , false , null , null , false , true ) ;

            $this->assertSame( ExitCode::SUCCESS , $status ) ;
            $this->assertSame( [] , CommandTraitMock::$commands ) ;
        }

        public function testSystemReturnsStatusOnSuccess(): void
        {
            CommandTraitMock::$systemStatus = ExitCode::SUCCESS ;

            $this->assertSame( ExitCode::SUCCESS , $this->trait->system( 'true' ) ) ;
        }

        public function testSystemVerboseLogsCommand(): void
        {
            $this->trait->system( 'true' , null , null , false , true ) ;

            $this->assertStringContainsString( '[▶] system:' , implode( "\n" , $this->trait->infos ) ) ;
        }

        public function testSystemThrowsOnFailure(): void
        {
            CommandTraitMock::$systemStatus = 2 ;

            try
            {
                $this->trait->system( 'false' ) ;
                $this->fail( 'Expected RuntimeException' ) ;
            }
            catch ( RuntimeException $e )
            {
                $this->assertStringContainsString( 'failed' , $e->getMessage() ) ;
                $this->assertSame( 2 , $e->getCode() ) ;
            }
        }

        public function testSystemWithSudoUsesSudoOptions(): void
        {
            CommandTraitMock::$systemStatus = ExitCode::SUCCESS ;

            $this->trait->system( 'whoami' , null , null , false , false , null , null , true ) ;

            $this->assertStringContainsString( 'sudo' , CommandTraitMock::lastCommand() ) ;
        }
    }
}
