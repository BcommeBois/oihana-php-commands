<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\ChownOptions;
use oihana\commands\traits\ChownTrait;

use PHPUnit\Framework\TestCase;

use RuntimeException;

use function oihana\files\getOwnershipInfos;

/**
 * Unit tests for ChownTrait.
 *
 * getOwnershipInfos() (imported via `use function`) cannot be shadowed, so the tests run
 * against a real temporary file and read its actual ownership. system() (from CommandTrait)
 * is overridden in the consumer to capture the call instead of executing it.
 */
class ChownTraitTest extends TestCase
{
    private string $path ;
    private ?string $currentOwner ;
    private ?string $currentGroup ;
    private object $trait ;

    protected function setUp(): void
    {
        $this->path = tempnam( sys_get_temp_dir() , 'chown_test_' ) ;

        $info = getOwnershipInfos( $this->path ) ;
        $this->currentOwner = $info->owner ;
        $this->currentGroup = $info->group ;

        $this->trait = new class
        {
            use ChownTrait;

            public array $systemCalls = [] ;
            public array $infos        = [] ;
            public array $warnings     = [] ;

            public function system
            (
                null|array|string $command  = null  ,
                null|array|string $args     = null  ,
                mixed             $options  = null  ,
                bool              $silent   = false ,
                bool              $verbose  = false ,
                ?string           $previous = null  ,
                ?string           $post     = null  ,
                bool              $sudo     = false ,
                bool              $dryRun   = false ,
            ): int
            {
                $this->systemCalls[] =
                [
                    'command' => $command ,
                    'args'    => $args    ,
                    'silent'  => $silent  ,
                    'verbose' => $verbose ,
                    'sudo'    => $sudo    ,
                ] ;
                return ExitCode::SUCCESS ;
            }

            public function info( $message = '' ): void
            {
                $this->infos[] = (string) $message ;
            }

            public function warning( $message = '' ): void
            {
                $this->warnings[] = (string) $message ;
            }
        } ;

        $this->trait->chownOptions = null ;
    }

    protected function tearDown(): void
    {
        if ( is_file( $this->path ) )
        {
            unlink( $this->path ) ;
        }
    }

    public function testSkipsWhenOwnershipAlreadyMatches(): void
    {
        $result = $this->trait->chown( $this->path , $this->currentOwner , $this->currentGroup ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    public function testSkipLogsInfoWhenVerbose(): void
    {
        $this->trait->chown( $this->path , $this->currentOwner , $this->currentGroup , null , false , true ) ;

        $this->assertStringContainsString( 'already matches' , implode( "\n" , $this->trait->infos ) ) ;
    }

    public function testStrictThrowsWhenOwnerAndGroupEmpty(): void
    {
        // owner '' is non-null (so it differs from the current owner -> needChown) but empty.
        $this->expectException( RuntimeException::class ) ;
        $this->expectExceptionMessage( 'You must provide at least an owner or a group for chown.' ) ;

        $this->trait->chown( $this->path , '' , null ) ;
    }

    public function testNonStrictEmptyOwnerGroupWarnsAndReturnsSuccess(): void
    {
        $result = $this->trait->chown( $this->path , '' , null , null , false , true , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertStringContainsString( 'at least an owner or a group' , implode( "\n" , $this->trait->warnings ) ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    public function testNonStrictEmptyOwnerGroupSilentReturnsSuccess(): void
    {
        $result = $this->trait->chown( $this->path , '' , null , null , false , false , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertSame( [] , $this->trait->warnings ) ;
    }

    public function testStrictThrowsWhenPathMissing(): void
    {
        $this->expectException( RuntimeException::class ) ;
        $this->expectExceptionMessage( 'Missing `path` for chown operation.' ) ;

        $this->trait->chown( null , 'www-data' , null ) ;
    }

    public function testNonStrictMissingPathWarnsAndReturnsSuccess(): void
    {
        $result = $this->trait->chown( null , 'www-data' , null , null , false , true , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertStringContainsString( 'Missing `path`' , implode( "\n" , $this->trait->warnings ) ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    public function testNonStrictMissingPathSilentReturnsSuccess(): void
    {
        $result = $this->trait->chown( null , 'www-data' , null , null , false , false , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertSame( [] , $this->trait->warnings ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    /**
     * A path that does not exist is a different failure from no path at all, and until now
     * only the second one honoured `strict`. The first raised from getOwnershipInfos()
     * whatever the caller asked for — which is what made a best-effort chown on a directory
     * about to be created abort the command instead.
     */
    private function missingPath(): string
    {
        return sys_get_temp_dir() . '/chown_test_absent_' . bin2hex( random_bytes( 8 ) ) ;
    }

    public function testStrictThrowsWhenPathDoesNotExist(): void
    {
        $path = $this->missingPath() ;

        $this->expectException( RuntimeException::class ) ;
        $this->expectExceptionMessage( sprintf( 'Path "%s" does not exist' , $path ) ) ;

        $this->trait->chown( $path , 'www-data' , null ) ;
    }

    public function testNonStrictNonExistentPathWarnsAndReturnsSuccess(): void
    {
        $path = $this->missingPath() ;

        $result = $this->trait->chown( $path , 'www-data' , null , null , false , true , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertStringContainsString( 'does not exist' , implode( "\n" , $this->trait->warnings ) ) ;
        $this->assertStringContainsString( $path , implode( "\n" , $this->trait->warnings ) ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    public function testNonStrictNonExistentPathSilentReturnsSuccess(): void
    {
        $result = $this->trait->chown( $this->missingPath() , 'www-data' , null , null , false , false , false ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertSame( [] , $this->trait->warnings ) ;
        $this->assertSame( [] , $this->trait->systemCalls ) ;
    }

    public function testRunsChownWithOwnerOnly(): void
    {
        $result = $this->trait->chown( $this->path , 'zzz_nobody' , null ) ;

        $this->assertSame( ExitCode::SUCCESS , $result ) ;
        $this->assertCount( 1 , $this->trait->systemCalls ) ;

        $call = $this->trait->systemCalls[ 0 ] ;
        $this->assertSame( 'chown' , $call[ 'command' ] ) ;
        $this->assertContains( 'zzz_nobody' , $call[ 'args' ] ) ;
        $this->assertContains( $this->path , $call[ 'args' ] ) ;
    }

    public function testRunsChownWithGroupOnly(): void
    {
        $this->trait->chown( $this->path , null , 'zzz_group' ) ;

        $call = $this->trait->systemCalls[ 0 ] ;
        $this->assertContains( 'zzz_group' , $call[ 'args' ] ) ;
    }

    public function testRunsChownWithOwnerAndGroup(): void
    {
        $this->trait->chown( $this->path , 'zzz_owner' , 'zzz_group' ) ;

        $call = $this->trait->systemCalls[ 0 ] ;
        $this->assertContains( 'zzz_owner:zzz_group' , $call[ 'args' ] ) ;
    }

    public function testSudoFlagFromParameter(): void
    {
        $this->trait->chown( $this->path , 'zzz_owner' , null , null , false , false , true , true ) ;

        $this->assertTrue( $this->trait->systemCalls[ 0 ][ 'sudo' ] ) ;
    }

    public function testSudoFlagFromOptions(): void
    {
        $options = new ChownOptions( [ 'sudo' => true ] ) ;

        $this->trait->chown( $this->path , 'zzz_owner' , null , $options ) ;

        $this->assertTrue( $this->trait->systemCalls[ 0 ][ 'sudo' ] ) ;
    }

    public function testSudoDefaultsToFalse(): void
    {
        $this->trait->chown( $this->path , 'zzz_owner' , null ) ;

        $this->assertFalse( $this->trait->systemCalls[ 0 ][ 'sudo' ] ) ;
    }

    public function testInitializeChownOptionsFromFlatArray(): void
    {
        $result = $this->trait->chown( $this->path , null , null ) ; // ensure baseline
        $this->assertSame( ExitCode::SUCCESS , $result ) ;

        $returned = $this->invokeInitialize( [ 'owner' => 'www-data' ] ) ;

        $this->assertSame( $this->trait , $returned ) ;
        $this->assertInstanceOf( ChownOptions::class , $this->trait->chownOptions ) ;
    }

    public function testInitializeChownOptionsFromChownKey(): void
    {
        $returned = $this->invokeInitialize( [ 'chown' => [ 'owner' => 'www-data' ] ] ) ;

        $this->assertSame( $this->trait , $returned ) ;
        $this->assertInstanceOf( ChownOptions::class , $this->trait->chownOptions ) ;
    }

    /**
     * initializeChownOptions() is protected; reach it through a closure bound to the consumer.
     */
    private function invokeInitialize( array $init ): object
    {
        $invoke = function( array $init )
        {
            return $this->initializeChownOptions( $init ) ;
        } ;
        return $invoke->call( $this->trait , $init ) ;
    }
}
