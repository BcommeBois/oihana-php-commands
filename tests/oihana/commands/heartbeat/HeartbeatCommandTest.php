<?php

declare(strict_types=1);

namespace tests\oihana\commands\heartbeat;

use Closure;
use RuntimeException;

use DI\Container;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;

use oihana\commands\enums\ExitCode;
use oihana\commands\enums\HeartbeatParam;
use oihana\commands\heartbeat\HeartbeatCommand;
use oihana\commands\heartbeat\HeartbeatState;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Symfony\Component\Console\Tester\CommandTester;

/**
 * The heartbeat: sent when the signal has an address, nothing done without one, and — when the
 * monitoring tool does not answer — one alert, then one per interval at most, without ever
 * printing the secret address.
 *
 * The HTTP client is a fake PSR-18 client: what is verified is the request it received and the
 * exit code given to the script that runs the command.
 */
#[CoversClass( HeartbeatCommand::class )]
final class HeartbeatCommandTest extends TestCase
{
    private const string TOKEN = 'AbCdEf1234' ;

    private const string URL = 'http://10.0.0.30:3001/api/push/' . self::TOKEN . '?status=up&msg=OK&ping=' ;

    private string $dir = '' ;

    /**
     * The requests the fake client received.
     * @var list<RequestInterface>
     */
    private array $requests = [] ;

    /**
     * The time the command believes it is.
     */
    private int $now = 1_000_000 ;

    protected function setUp() :void
    {
        $this->dir      = sys_get_temp_dir() . '/oihana-heartbeat-' . bin2hex( random_bytes( 6 ) ) ;
        $this->requests = [] ;
        $this->now      = 1_000_000 ;

        mkdir( $this->dir ) ;
    }

    protected function tearDown() :void
    {
        foreach( glob( $this->dir . '/*' ) ?: [] as $file )
        {
            unlink( $file ) ;
        }
        rmdir( $this->dir ) ;
    }

    /**
     * A PSR-18 client that answers, in order, the responses or throws the errors it is given.
     *
     * @param list<ResponseInterface|ClientExceptionInterface> $answers
     */
    private function client( array $answers ) :ClientInterface
    {
        return new class( $answers , $this->requests ) implements ClientInterface
        {
            /**
             * @param list<ResponseInterface|ClientExceptionInterface> $answers
             * @param list<RequestInterface>                           $requests
             */
            public function __construct( private array $answers , private array &$requests ) {}

            public function sendRequest( RequestInterface $request ) :ResponseInterface
            {
                $this->requests[] = $request ;
                $answer = array_shift( $this->answers ) ;

                if( $answer instanceof ClientExceptionInterface )
                {
                    throw $answer ;
                }

                return $answer ?? new Response( 200 ) ;
            }
        } ;
    }

    /**
     * A refused connection, its message quoting the address it tried — as cURL does.
     */
    private function refused( string $message = '' ) :ClientExceptionInterface
    {
        $message = $message === '' ? 'cURL error 7: Failed to connect to 10.0.0.30 port 3001 for ' . self::URL : $message ;

        return new class( $message ) extends RuntimeException implements ClientExceptionInterface {} ;
    }

    /**
     * Runs the command once.
     *
     * @param list<ResponseInterface|ClientExceptionInterface> $answers What the tool answers, in order.
     * @param array<string, mixed>                             $init    Overrides of the initialization.
     */
    private function beat( array $answers , array $init = [] , string $signal = 'cron' ) :CommandTester
    {
        $init =
        [
            HeartbeatParam::SIGNALS         => [ 'cron' => self::URL ] ,
            HeartbeatParam::CLIENT          => $this->client( $answers ) ,
            HeartbeatParam::REQUEST_FACTORY => new HttpFactory() ,
            HeartbeatParam::STATE_DIR       => $this->dir ,
            ...$init ,
        ] ;

        $clock   = fn() :int => $this->now ;
        $command = new class( 'test:heartbeat' , new Container() , $init , $clock ) extends HeartbeatCommand
        {
            /**
             * @param array<string, mixed> $init
             */
            public function __construct( ?string $name , Container $container , array $init , private Closure $clock )
            {
                parent::__construct( $name , $container , $init ) ;
            }

            protected function now() :int
            {
                return ( $this->clock )() ;
            }
        } ;

        $tester = new CommandTester( $command ) ;
        $tester->execute( [ HeartbeatCommand::SIGNAL => $signal ] ) ;

        return $tester ;
    }

    private function state() :HeartbeatState
    {
        return HeartbeatState::read( $this->dir . '/heartbeat-cron.json' ) ;
    }

    public function testTheInitializationIsRead() :void
    {
        $client  = $this->client( [] ) ;
        $factory = new HttpFactory() ;
        $command = new HeartbeatCommand( 'test:heartbeat' , new Container() ,
        [
            HeartbeatParam::ALERT_EVERY     => 600 ,
            HeartbeatParam::CLIENT          => $client ,
            HeartbeatParam::REQUEST_FACTORY => $factory ,
            HeartbeatParam::SIGNALS         => [ 'cron' => self::URL ] ,
            HeartbeatParam::STATE_DIR       => $this->dir ,
        ]) ;

        $this->assertSame( 600 , $command->alertEvery ) ;
        $this->assertSame( $client , $command->client ) ;
        $this->assertSame( $factory , $command->requestFactory ) ;
        $this->assertSame( [ 'cron' => self::URL ] , $command->signals ) ;
        $this->assertSame( $this->dir , $command->stateDir ) ;
    }

    public function testAWrongInitializationFallsBackToTheDefaults() :void
    {
        $command = new HeartbeatCommand( 'test:heartbeat' , new Container() ,
        [
            HeartbeatParam::ALERT_EVERY     => -5 ,
            HeartbeatParam::CLIENT          => 'not a client' ,
            HeartbeatParam::REQUEST_FACTORY => 'not a factory' ,
            HeartbeatParam::SIGNALS         => 'not a list' ,
            HeartbeatParam::STATE_DIR       => '' ,
        ]) ;

        $this->assertSame( HeartbeatCommand::DEFAULT_ALERT_EVERY , $command->alertEvery ) ;
        $this->assertNull( $command->client ) ;
        $this->assertNull( $command->requestFactory ) ;
        $this->assertSame( [] , $command->signals ) ;
        $this->assertNull( $command->stateDir ) ;
    }

    public function testASignalTakenEndsOnZeroAndCallsTheAddressAsItIs() :void
    {
        $tester = $this->beat( [ new Response( 200 , [] , '{"ok":true}' ) ] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertCount( 1 , $this->requests ) ;
        $this->assertSame( 'GET' , $this->requests[ 0 ]->getMethod() ) ;
        $this->assertSame( self::URL , (string) $this->requests[ 0 ]->getUri() ) ;
        $this->assertStringContainsString( 'sent to http://10.0.0.30:3001.' , $tester->getDisplay() ) ;
        $this->assertFalse( $this->state()->isFailing() ) ;
    }

    public function testAnyTwoHundredWithoutABodyIsTaken() :void
    {
        $this->assertSame( ExitCode::SUCCESS , $this->beat( [ new Response( 204 ) ] )->getStatusCode() ) ;
        $this->assertSame( ExitCode::SUCCESS , $this->beat( [ new Response( 200 , [] , 'OK' ) ] )->getStatusCode() ) ;
    }

    public function testAJsonBodyWithoutOkIsTaken() :void
    {
        $this->assertSame( ExitCode::SUCCESS , $this->beat( [ new Response( 200 , [] , '{"status":"accepted"}' ) ] )->getStatusCode() ) ;
    }

    public function testAnInformationalStatusIsAFailure() :void
    {
        $tester = $this->beat( [ new Response( 100 ) ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'HTTP 100' , $tester->getDisplay() ) ;
    }

    public function testAnAddressIsTrimmed() :void
    {
        $this->beat( [ new Response( 200 ) ] , [ HeartbeatParam::SIGNALS => [ 'cron' => '  ' . self::URL . ' ' ] ] ) ;

        $this->assertSame( self::URL , (string) $this->requests[ 0 ]->getUri() ) ;
    }

    public function testAnAddressWithoutAPortPrintsItsHostOnly() :void
    {
        $tester = $this->beat( [ new Response( 200 ) ] , [ HeartbeatParam::SIGNALS => [ 'cron' => 'https://monitor.example.com/ping/' . self::TOKEN ] ] ) ;

        $this->assertStringContainsString( 'sent to https://monitor.example.com.' , $tester->getDisplay() ) ;
    }

    public function testAnAddressWithoutSchemeNorHostIsStillHidden() :void
    {
        $tester = $this->beat( [ $this->refused( 'no route for ' . self::TOKEN ) ] , [ HeartbeatParam::SIGNALS => [ 'cron' => '/push/' . self::TOKEN ] ] ) ;

        $this->assertStringContainsString( 'http://?' , $tester->getDisplay() ) ;
        $this->assertStringNotContainsString( self::TOKEN , $tester->getDisplay() ) ;
    }

    public function testNoAddressDoesNothing() :void
    {
        $tester = $this->beat( [] , [ HeartbeatParam::SIGNALS => [ 'cron' => '  ' ] ] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertCount( 0 , $this->requests ) ;
        $this->assertStringContainsString( 'nothing sent' , $tester->getDisplay() ) ;
    }

    public function testAnUnknownSignalDoesNothing() :void
    {
        $tester = $this->beat( [] , [] , 'other' ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertCount( 0 , $this->requests ) ;
    }

    public function testAnAddressThatIsNotAStringDoesNothing() :void
    {
        $this->assertSame( ExitCode::SUCCESS , $this->beat( [] , [ HeartbeatParam::SIGNALS => [ 'cron' => 42 ] ] )->getStatusCode() ) ;
        $this->assertCount( 0 , $this->requests ) ;
    }

    public function testASignalThatIsNotANameIsRefused() :void
    {
        $tester = $this->beat( [] , [] , '../etc' ) ;

        $this->assertSame( ExitCode::INVALID , $tester->getStatusCode() ) ;
        $this->assertCount( 0 , $this->requests ) ;
        $this->assertStringContainsString( 'is not a name' , $tester->getDisplay() ) ;
    }

    public function testWithoutAClientNothingCanBeSent() :void
    {
        $tester = $this->beat( [] , [ HeartbeatParam::CLIENT => null ] ) ;

        $this->assertSame( ExitCode::INVALID , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'No HTTP client' , $tester->getDisplay() ) ;
    }

    public function testWithoutARequestFactoryNothingCanBeSent() :void
    {
        $this->assertSame( ExitCode::INVALID , $this->beat( [] , [ HeartbeatParam::REQUEST_FACTORY => null ] )->getStatusCode() ) ;
        $this->assertCount( 0 , $this->requests ) ;
    }

    public function testTheFirstFailureAlerts() :void
    {
        $tester = $this->beat( [ $this->refused() ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertSame( 1_000_000 , $this->state()->since ) ;
        $this->assertSame( 1_000_000 , $this->state()->alerted ) ;
        $this->assertStringContainsString( 'this pass alerts' , $tester->getDisplay() ) ;
    }

    public function testTheNextFailuresOfTheIntervalDoNotAlert() :void
    {
        $this->beat( [ $this->refused() ] ) ;

        $this->now += 300 ;
        $tester = $this->beat( [ $this->refused( 'cURL error 28: Operation timed out' ) ] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertSame( 1_000_000 , $this->state()->since ) ;
        $this->assertSame( 1_000_000 , $this->state()->alerted ) ;
        $this->assertSame( 'cURL error 28: Operation timed out' , $this->state()->error , 'the last error is remembered' ) ;
        $this->assertStringContainsString( 'no new alert before' , $tester->getDisplay() ) ;
    }

    public function testAFailureThatLastsAlertsAgainAfterTheInterval() :void
    {
        $this->beat( [ $this->refused() ] ) ;

        $this->now += 3600 ;
        $tester = $this->beat( [ $this->refused() ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertSame( 1_000_000 , $this->state()->since ) ;
        $this->assertSame( 1_003_600 , $this->state()->alerted ) ;
    }

    public function testTheIntervalIsTheOneGiven() :void
    {
        $this->beat( [ $this->refused() ] , [ HeartbeatParam::ALERT_EVERY => 600 ] ) ;

        $this->now += 599 ;
        $this->assertSame( ExitCode::SUCCESS , $this->beat( [ $this->refused() ] , [ HeartbeatParam::ALERT_EVERY => 600 ] )->getStatusCode() ) ;

        $this->now += 1 ;
        $this->assertSame( ExitCode::FAILURE , $this->beat( [ $this->refused() ] , [ HeartbeatParam::ALERT_EVERY => 600 ] )->getStatusCode() ) ;
    }

    public function testTheReturnOfTheToolClearsTheFailure() :void
    {
        $this->beat( [ $this->refused() ] ) ;

        $this->now += 900 ;
        $tester = $this->beat( [ new Response( 200 ) ] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertFileDoesNotExist( $this->dir . '/heartbeat-cron.json' ) ;
        $this->assertStringContainsString( 'answers again' , $tester->getDisplay() ) ;

        $this->now += 300 ;
        $this->assertSame( ExitCode::FAILURE , $this->beat( [ $this->refused() ] )->getStatusCode() , 'a new failure alerts at once' ) ;
    }

    public function testAStatusOutOfTwoHundredIsAFailure() :void
    {
        $tester = $this->beat( [ new Response( 502 , [] , 'Bad Gateway' ) ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'HTTP 502' , $tester->getDisplay() ) ;
    }

    public function testARedirectionIsAFailure() :void
    {
        $this->assertSame( ExitCode::FAILURE , $this->beat( [ new Response( 302 ) ] )->getStatusCode() ) ;
    }

    public function testASignalTheToolRefusesIsAFailure() :void
    {
        $tester = $this->beat( [ new Response( 404 , [] , '{"ok":false,"msg":"Monitor not found or not active."}' ) ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'refused the signal (HTTP 404): Monitor not found or not active.' , $tester->getDisplay() ) ;
    }

    public function testARefusalEvenWithATwoHundredIsAFailure() :void
    {
        $tester = $this->beat( [ new Response( 200 , [] , '{"ok":false}' ) ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'refused the signal (HTTP 200)' , $tester->getDisplay() ) ;
    }

    public function testTheSecretAddressIsNeverPrintedNorRemembered() :void
    {
        $tester = $this->beat( [ $this->refused() ] ) ;

        $this->assertStringNotContainsString( self::TOKEN , $tester->getDisplay() ) ;
        $this->assertStringNotContainsString( self::TOKEN , (string) file_get_contents( $this->dir . '/heartbeat-cron.json' ) ) ;
        $this->assertStringContainsString( 'http://10.0.0.30:3001/***' , $tester->getDisplay() ) ;
    }

    public function testTheTokenAloneIsHiddenToo() :void
    {
        $tester = $this->beat( [ $this->refused( 'No route to /api/push/' . self::TOKEN ) ] ) ;

        $this->assertStringNotContainsString( self::TOKEN , $tester->getDisplay() ) ;
        $this->assertStringContainsString( '/api/push/***' , $tester->getDisplay() ) ;
    }

    public function testAnAddressWithoutPathHidesTheAddressOnly() :void
    {
        $tester = $this->beat( [ $this->refused( 'Failed for http://10.0.0.30:3001' ) ] , [ HeartbeatParam::SIGNALS => [ 'cron' => 'http://10.0.0.30:3001' ] ] ) ;

        $this->assertStringContainsString( 'Failed for http://10.0.0.30:3001/***' , $tester->getDisplay() ) ;
    }

    public function testWithoutAStateDirectoryEveryFailureAlerts() :void
    {
        $init = [ HeartbeatParam::STATE_DIR => $this->dir . '/missing' ] ;

        $this->assertSame( ExitCode::FAILURE , $this->beat( [ $this->refused() ] , $init )->getStatusCode() ) ;

        $this->now += 300 ;
        $tester = $this->beat( [ $this->refused() ] , $init ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->getStatusCode() ) ;
        $this->assertStringContainsString( 'No state directory' , $tester->getDisplay() ) ;
    }

    public function testWithoutAStateDirectoryTheReturnIsSilent() :void
    {
        $tester = $this->beat( [ new Response( 200 ) ] , [ HeartbeatParam::STATE_DIR => null ] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->getStatusCode() ) ;
        $this->assertStringNotContainsString( 'answers again' , $tester->getDisplay() ) ;
    }

    public function testTheRealClockIsUsedByDefault() :void
    {
        $command = new HeartbeatCommand( 'test:heartbeat' , new Container() ,
        [
            HeartbeatParam::SIGNALS         => [ 'cron' => self::URL ] ,
            HeartbeatParam::CLIENT          => $this->client( [ $this->refused() ] ) ,
            HeartbeatParam::REQUEST_FACTORY => new HttpFactory() ,
            HeartbeatParam::STATE_DIR       => $this->dir ,
        ]) ;

        $before = time() ;
        new CommandTester( $command )->execute( [ HeartbeatCommand::SIGNAL => 'cron' ] ) ;

        $this->assertGreaterThanOrEqual( $before , $this->state()->since ) ;
        $this->assertLessThanOrEqual( time() , $this->state()->since ) ;
    }
}
