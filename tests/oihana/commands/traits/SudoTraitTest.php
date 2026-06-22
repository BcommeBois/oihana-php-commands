<?php

declare(strict_types=1);

namespace oihana\commands\traits
{
    /**
     * Shadows the global exec() for code running in the oihana\commands\traits namespace.
     * PHP resolves an unqualified function call in the current namespace before falling back
     * to the global one, so SudoTrait's exec() calls are routed to the test registry.
     * Declared once here; ChownTrait uses system() (not exec()) so there is no collision.
     */
    function exec( string $command , &$output = null , &$result_code = null )
    {
        if ( !is_array( $output ) )
        {
            $output = [] ;
        }

        [ $lines , $code ] = \tests\oihana\commands\traits\SudoExecMock::respond( $command ) ;

        foreach ( $lines as $line )
        {
            $output[] = $line ;
        }

        $result_code = $code ;

        return $lines ? end( $lines ) : '' ;
    }
}

namespace tests\oihana\commands\traits
{
    use oihana\commands\enums\ExitCode;
    use oihana\commands\options\CommandOptions;
    use oihana\commands\traits\SudoTrait;

    use PHPUnit\Framework\TestCase;

    use RuntimeException;

    /**
     * Static registry driving the shadowed exec(): records commands and returns scripted
     * [output, exitCode] pairs based on the command string.
     */
    final class SudoExecMock
    {
        /** @var array<int,string> */
        public static array $commands = [] ;

        /** @var (callable(string):array{0:array,1:int})|null */
        public static $responder = null ;

        public static function reset(): void
        {
            self::$commands  = [] ;
            self::$responder = null ;
        }

        /** @return array{0:array,1:int} */
        public static function respond( string $command ): array
        {
            self::$commands[] = $command ;
            return self::$responder === null ? [ [] , 0 ] : ( self::$responder )( $command ) ;
        }
    }

    /**
     * Unit tests for SudoTrait
     */
    class SudoTraitTest extends TestCase
    {
        protected function setUp(): void
        {
            SudoExecMock::reset() ;
        }

        private function fixture(): object
        {
            return new class
            {
                use SudoTrait;

                public array $infos    = [] ;
                public array $warnings = [] ;

                public function info( $message = '' ): void
                {
                    $this->infos[] = (string) $message ;
                }

                public function warning( $message = '' ): void
                {
                    $this->warnings[] = (string) $message ;
                }
            } ;
        }

        private function options( bool $sudo ): CommandOptions
        {
            $options = new CommandOptions() ;
            $options->sudo = $sudo ;
            return $options ;
        }

        // ----------------------------------------------------- sudoAuthenticate

        public function testSkipsWhenSudoNotRequired(): void
        {
            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( false ) ) ;

            $this->assertSame( ExitCode::SUCCESS , $result ) ;
            $this->assertSame( [] , SudoExecMock::$commands ) ;
            $this->assertSame( [] , $fixture->infos ) ;
        }

        public function testSkipsWhenSudoNotRequiredVerbose(): void
        {
            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( false ) , false , false , true ) ;

            $this->assertSame( ExitCode::SUCCESS , $result ) ;
            $this->assertStringContainsString( 'sudo is not required' , $fixture->infos[ 0 ] ) ;
        }

        public function testThrowsWhenSudoNotFound(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => str_contains( $cmd , 'command -v sudo' ) ? [ [] , 1 ] : [ [] , 0 ] ;

            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'sudo command not found on this system.' ) ;

            $this->fixture()->sudoAuthenticate( $this->options( true ) ) ;
        }

        public function testThrowsWhenAuthenticationFails(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => str_contains( $cmd , 'command -v sudo' ) ? [ [] , 0 ] : [ [] , 1 ] ;

            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'Sudo authentication failed or was cancelled.' ) ;

            $this->fixture()->sudoAuthenticate( $this->options( true ) ) ;
        }

        public function testAuthenticatesSuccessfully(): void
        {
            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( true ) ) ;

            $this->assertSame( ExitCode::SUCCESS , $result ) ;
            $this->assertContains( 'command -v sudo' , SudoExecMock::$commands ) ;
            $this->assertNotEmpty( array_filter( SudoExecMock::$commands , fn( $c ) => str_contains( $c , 'sudo -v' ) ) ) ;
        }

        public function testAuthenticatesSuccessfullyVerbose(): void
        {
            $fixture = $this->fixture() ;

            $fixture->sudoAuthenticate( $this->options( true ) , false , false , true ) ;

            $joined = implode( "\n" , $fixture->infos ) ;
            $this->assertStringContainsString( 'Starting sudo authentication' , $joined ) ;
            $this->assertStringContainsString( 'verbose mode enabled' , $joined ) ;
            $this->assertStringContainsString( 'Sudo authentication succeeded' , $joined ) ;
        }

        public function testKeepAliveStartsSuccessfully(): void
        {
            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( true ) , true , false , true ) ;

            $this->assertSame( ExitCode::SUCCESS , $result ) ;
            $this->assertNotEmpty( array_filter( SudoExecMock::$commands , fn( $c ) => str_contains( $c , 'nohup' ) ) ) ;
            $this->assertStringContainsString( 'keep-alive process started' , implode( "\n" , $fixture->infos ) ) ;
        }

        public function testKeepAliveFailureReturnsCodeVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => str_contains( $cmd , 'nohup' ) ? [ [] , 5 ] : [ [] , 0 ] ;

            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( true ) , true , false , true ) ;

            $this->assertSame( 5 , $result ) ;
            $this->assertStringContainsString( 'Failed to start background sudo keep-alive' , implode( "\n" , $fixture->warnings ) ) ;
        }

        public function testKeepAliveFailureNonVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => str_contains( $cmd , 'nohup' ) ? [ [] , 7 ] : [ [] , 0 ] ;

            $fixture = $this->fixture() ;

            $result = $fixture->sudoAuthenticate( $this->options( true ) , true , false , false ) ;

            $this->assertSame( 7 , $result ) ;
            $this->assertSame( [] , $fixture->warnings ) ;
        }

        public function testSilentModeAddsRedirect(): void
        {
            $fixture = $this->fixture() ;

            $fixture->sudoAuthenticate( $this->options( true ) , false , true , false ) ;

            $this->assertNotEmpty( array_filter( SudoExecMock::$commands , fn( $c ) => str_contains( $c , '/dev/null 2>&1' ) ) ) ;
        }

        // ----------------------------------------------------- sudoStopKeepAlive

        public function testStopKeepAliveNoProcess(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => [ [] , 0 ] ;

            $fixture = $this->fixture() ;

            $this->assertFalse( $fixture->sudoStopKeepAlive() ) ;
            $this->assertSame( [] , $fixture->warnings ) ;
        }

        public function testStopKeepAliveNoProcessVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => [ [] , 0 ] ;

            $fixture = $this->fixture() ;

            $this->assertFalse( $fixture->sudoStopKeepAlive( true ) ) ;
            $this->assertStringContainsString( 'No sudo keep-alive process found' , implode( "\n" , $fixture->warnings ) ) ;
        }

        public function testStopKeepAliveKillsProcessesVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) => str_contains( $cmd , 'pgrep' ) ? [ [ '123' , '456' ] , 0 ] : [ [] , 0 ] ;

            $fixture = $this->fixture() ;

            $this->assertTrue( $fixture->sudoStopKeepAlive( true ) ) ;
            $this->assertContains( 'kill 123' , SudoExecMock::$commands ) ;
            $this->assertContains( 'kill 456' , SudoExecMock::$commands ) ;

            $joined = implode( "\n" , $fixture->infos ) ;
            $this->assertStringContainsString( 'Killed sudo keep-alive process 123' , $joined ) ;
            $this->assertStringContainsString( 'Killed sudo keep-alive process 456' , $joined ) ;
        }

        public function testStopKeepAliveKillFailureVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) =>
                str_contains( $cmd , 'pgrep' ) ? [ [ '123' ] , 0 ] : [ [] , 1 ] ;

            $fixture = $this->fixture() ;

            $this->assertFalse( $fixture->sudoStopKeepAlive( true ) ) ;
            $this->assertStringContainsString( 'Failed to kill process 123' , implode( "\n" , $fixture->warnings ) ) ;
        }

        public function testStopKeepAliveKillFailureNonVerbose(): void
        {
            SudoExecMock::$responder = fn( string $cmd ) =>
                str_contains( $cmd , 'pgrep' ) ? [ [ '123' ] , 0 ] : [ [] , 1 ] ;

            $fixture = $this->fixture() ;

            $this->assertFalse( $fixture->sudoStopKeepAlive( false ) ) ;
            $this->assertSame( [] , $fixture->warnings ) ;
        }
    }
}
