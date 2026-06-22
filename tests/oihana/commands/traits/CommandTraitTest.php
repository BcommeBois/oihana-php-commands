<?php

declare(strict_types=1);

namespace oihana\commands\traits
{
    /**
     * Shadow the global process functions for code in the oihana\commands\traits namespace.
     * Only CommandTrait calls these unqualified, so the shadows are scoped to its tests.
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

    function proc_open( $command , $descriptors , &$pipes , $cwd = null , $env = null , $options = null )
    {
        \tests\oihana\commands\traits\CommandTraitMock::$commands[] = $command ;

        if ( \tests\oihana\commands\traits\CommandTraitMock::$procOpenFails )
        {
            return false ;
        }

        $pipes =
        [
            \fopen( 'php://memory' , 'r' ) ,
            \fopen( 'php://memory' , 'r' ) ,
            \fopen( 'php://memory' , 'r' ) ,
        ] ;

        return \fopen( 'php://memory' , 'r' ) ;
    }

    function stream_get_contents( $stream , ?int $length = null , int $offset = -1 )
    {
        return array_shift( \tests\oihana\commands\traits\CommandTraitMock::$streamQueue ) ?? '' ;
    }

    function fclose( $stream ): bool
    {
        return is_resource( $stream ) ? \fclose( $stream ) : true ;
    }

    function proc_close( $process )
    {
        if ( is_resource( $process ) )
        {
            \fclose( $process ) ;
        }
        return \tests\oihana\commands\traits\CommandTraitMock::$procExit ;
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
        public static int     $procExit     = 0 ;
        public static array   $streamQueue  = [] ;
        public static bool    $procOpenFails = false ;

        public static function reset(): void
        {
            self::$commands      = [] ;
            self::$shellOutput   = '' ;
            self::$systemStatus  = 0 ;
            self::$procExit      = 0 ;
            self::$streamQueue   = [] ;
            self::$procOpenFails = false ;
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
            CommandTraitMock::$streamQueue = [ "  out  " , "  err  " ] ;
            CommandTraitMock::$procExit    = 3 ;

            $process = $this->trait->proc( 'ls' ) ;

            $this->assertSame( 'out' , $process->output ) ;
            $this->assertSame( 'err' , $process->error ) ;
            $this->assertSame( 3 , $process->status ) ;
        }

        public function testProcVerboseLogsCommand(): void
        {
            $this->trait->proc( 'ls' , null , null , true ) ;

            $this->assertStringContainsString( '[▶] proc:' , implode( "\n" , $this->trait->infos ) ) ;
        }

        public function testProcThrowsWhenProcessCannotStart(): void
        {
            CommandTraitMock::$procOpenFails = true ;

            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'Failed to execute the command' ) ;

            $this->trait->proc( 'ls' ) ;
        }

        public function testProcWithSudoUsesSudoOptions(): void
        {
            CommandTraitMock::$streamQueue = [ 'o' , 'e' ] ;

            $this->trait->proc( 'whoami' , null , null , false , null , null , true ) ;

            $this->assertStringContainsString( 'sudo' , CommandTraitMock::lastCommand() ) ;
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
