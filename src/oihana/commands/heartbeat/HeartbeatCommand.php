<?php

namespace oihana\commands\heartbeat;

use Throwable;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use oihana\commands\enums\ExitCode;
use oihana\commands\enums\HeartbeatParam;
use oihana\commands\Kernel;

use oihana\enums\http\HttpMethod;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends a heartbeat — a sign of life — to a monitoring tool, and warns when the tool does not answer.
 *
 * A job that fails can send an alert; a machine where **nothing runs any more** — stopped, its
 * scheduler down, its crontab emptied — fails at nothing, and stays silent. A monitoring tool on
 * another machine (Uptime Kuma, Healthchecks…) closes that gap: it gives a **push address** and
 * waits for a call every few minutes; when the calls stop, it is the one that alerts.
 *
 * ```shell
 * bin/console myapp:heartbeat cron        # run every 5 minutes by the scheduler
 * ```
 *
 * The call also watches the tool the other way round. When the tool does not answer — a
 * timeout, a refused connection, a status out of 2xx, or a JSON body whose `ok` is `false` (how
 * Uptime Kuma refuses a push) —, the command ends on **`1`**, so the script that runs it can
 * alert. **Once** when the failure starts, then once every `alertEvery` seconds at most while it
 * lasts: the other passes end on `0` and only write the failure. The failure is remembered in a
 * small file per signal ({@see HeartbeatState}); without a directory, every failed pass alerts.
 *
 * | Exit | When                                                                          |
 * | :--- | :---------------------------------------------------------------------------- |
 * | `0`  | the tool took the signal; or the signal has no address; or the alert is not due |
 * | `1`  | the tool does not answer and an alert is due                                  |
 * | `2`  | the signal is not a name, or no HTTP client or request factory was given      |
 *
 * Initialization (`$init`), see {@see HeartbeatParam}:
 *
 * - `signals` — the signals: their name → their push address, called **as it is** (a tool that
 *   wants a query, e.g. `?status=up`, gives an address that carries it) ;
 * - `client` — a PSR-18 HTTP client, with its own timeout ;
 * - `requestFactory` — a PSR-17 request factory ;
 * - `stateDir` — where the failures are remembered ;
 * - `alertEvery` — the seconds between two alerts of the same failure, 3600 by default.
 *
 * 🔒 **A push address is a secret**: whoever knows it can say the machine is alive. The command
 * only prints its scheme, host and port; in an error message, the address and its last segment —
 * the token, for the tools that put it there — become `***`.
 *
 * @package oihana\commands\heartbeat
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class HeartbeatCommand extends Kernel
{
    /**
     * Creates the command.
     *
     * @param string|null          $name      The name of the command.
     * @param Container            $container The dependency injection container.
     * @param array<string, mixed> $init      The initialization parameters, see {@see HeartbeatParam}.
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     */
    public function __construct( ?string $name , Container $container , array $init = [] )
    {
        $alertEvery = $init[ HeartbeatParam::ALERT_EVERY     ] ?? null ;
        $client     = $init[ HeartbeatParam::CLIENT          ] ?? null ;
        $factory    = $init[ HeartbeatParam::REQUEST_FACTORY ] ?? null ;
        $signals    = $init[ HeartbeatParam::SIGNALS         ] ?? null ;
        $stateDir   = $init[ HeartbeatParam::STATE_DIR       ] ?? null ;

        $this->alertEvery     = is_int( $alertEvery ) && $alertEvery > 0 ? $alertEvery : self::DEFAULT_ALERT_EVERY ;
        $this->client         = $client  instanceof ClientInterface         ? $client  : null ;
        $this->requestFactory = $factory instanceof RequestFactoryInterface ? $factory : null ;
        $this->signals        = is_array( $signals ) ? $signals : [] ;
        $this->stateDir       = is_string( $stateDir ) && $stateDir !== '' ? $stateDir : null ;

        parent::__construct( $name , $container , $init ) ;
    }

    /**
     * The seconds between two alerts of the same failure, by default.
     */
    public const int DEFAULT_ALERT_EVERY = 3600 ;


    /**
     * What replaces a push address, or its token, in a printed message.
     */
    public const string HIDDEN = '***' ;


    /**
     * The key of a JSON answer saying why the tool refused the signal.
     */
    public const string MESSAGE = 'msg' ;


    /**
     * The key of a JSON answer saying whether the tool took the signal (`false`: refused).
     */
    public const string OK = 'ok' ;


    /**
     * The name of the argument naming the signal.
     */
    public const string SIGNAL = 'signal' ;


    /**
     * The characters a signal name may hold: it names a file of the state directory.
     */
    public const string SIGNAL_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/' ;

    /**
     * The seconds between two alerts of the same failure.
     */
    public int $alertEvery ;

    /**
     * The PSR-18 HTTP client, or `null` when none was given.
     */
    public ?ClientInterface $client ;

    /**
     * The PSR-17 request factory, or `null` when none was given.
     */
    public ?RequestFactoryInterface $requestFactory ;

    /**
     * The signals: their name → their push address.
     *
     * @var array<mixed>
     */
    public array $signals ;

    /**
     * The directory where the failures are remembered, or `null`.
     */
    public ?string $stateDir ;

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure() :void
    {
        $this->addArgument( self::SIGNAL , InputArgument::REQUIRED , 'The signal to send, as named in the signals of the command.' ) ;
    }

    /**
     * Sends the signal.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute( InputInterface $input , OutputInterface $output ) :int
    {
        [ $io , $timestamp ] = $this->startCommand( $input , $output ) ;

        $signal = $input->getArgument( self::SIGNAL ) ;
        $signal = is_string( $signal ) ? $signal : '' ;

        if( preg_match( self::SIGNAL_PATTERN , $signal ) !== 1 )
        {
            $io->error( sprintf( 'The signal "%s" is not a name: lowercase letters, digits, "-" and "_".' , $signal ) ) ;
            return $this->endCommand( $input , $output , ExitCode::INVALID , $timestamp ) ;
        }

        $url = $this->signals[ $signal ] ?? null ;
        $url = is_string( $url ) ? trim( $url ) : '' ;

        if( $url === '' )
        {
            $io->writeln( sprintf( '🔕 No push address for the signal "%s": nothing sent.' , $signal ) ) ;
            return $this->endCommand( $input , $output , ExitCode::SUCCESS , $timestamp ) ;
        }

        if( $this->client === null || $this->requestFactory === null )
        {
            $io->error( 'No HTTP client or no request factory: the signal cannot be sent.' ) ;
            return $this->endCommand( $input , $output , ExitCode::INVALID , $timestamp ) ;
        }

        $host  = $this->host( $url ) ;
        $file  = $this->stateDir !== null && is_dir( $this->stateDir ) ? rtrim( $this->stateDir , '/' ) . '/heartbeat-' . $signal . '.json' : null ;
        $state = $file === null ? new HeartbeatState() : HeartbeatState::read( $file ) ;
        $error = $this->push( $this->client , $this->requestFactory , $url ) ;

        if( $error === null )
        {
            if( $state->isFailing() && $file !== null )
            {
                HeartbeatState::clear( $file ) ;
                $io->writeln( sprintf( '🟢 The watcher answers again (%s), after a failure since %s.' , $host , $this->date( $state->since ) ) ) ;
            }

            $io->writeln( sprintf( '✅ Signal "%s" sent to %s.' , $signal , $host ) ) ;
            return $this->endCommand( $input , $output , ExitCode::SUCCESS , $timestamp ) ;
        }

        $error = $this->hide( $error , $url ) ;
        $now   = $this->now() ;
        $state = $state->fail( $now , $error ) ;

        $io->writeln( sprintf( '❌ The watcher does not answer (%s): %s' , $host , $error ) ) ;

        if( $file === null )
        {
            $io->writeln( '⚠️  No state directory: the failure cannot be remembered, every failed pass alerts.' ) ;
            return $this->endCommand( $input , $output , ExitCode::FAILURE , $timestamp ) ;
        }

        if( $state->isAlertDue( $now , $this->alertEvery ) )
        {
            $state->alert( $now )->write( $file ) ;
            $io->writeln( sprintf( '📣 Failing since %s: this pass alerts, the next alert not before %s.' , $this->date( $state->since ) , $this->date( $now + $this->alertEvery ) ) ) ;
            return $this->endCommand( $input , $output , ExitCode::FAILURE , $timestamp ) ;
        }

        $state->write( $file ) ;
        $io->writeln( sprintf
        (
            '🔇 Failing since %s, alerted at %s: no new alert before %s.' ,
            $this->date( $state->since ) ,
            $this->date( $state->alerted ) ,
            $this->date( ( $state->alerted ?? $now ) + $this->alertEvery )
        )) ;

        return $this->endCommand( $input , $output , ExitCode::SUCCESS , $timestamp ) ;
    }

    /**
     * The current time, in seconds.
     *
     * @return int
     */
    protected function now() :int
    {
        return time() ;
    }

    /**
     * Calls a push address.
     *
     * @param ClientInterface         $client  The HTTP client.
     * @param RequestFactoryInterface $factory The request factory.
     * @param string                  $url     The push address.
     *
     * @return string|null What failed, or `null` when the tool took the signal.
     */
    protected function push( ClientInterface $client , RequestFactoryInterface $factory , string $url ) :?string
    {
        try
        {
            $response = $client->sendRequest( $factory->createRequest( HttpMethod::GET , $url ) ) ;
            $status   = $response->getStatusCode() ;
            $body     = json_decode( (string) $response->getBody() , true ) ;

            if( is_array( $body ) && ( $body[ self::OK ] ?? null ) === false )
            {
                $message = $body[ self::MESSAGE ] ?? null ;
                return sprintf( 'the watcher refused the signal (HTTP %d)' , $status ) . ( is_string( $message ) ? ': ' . $message : '' ) ;
            }

            return $status >= 200 && $status < 300 ? null : sprintf( 'HTTP %d' , $status ) ;
        }
        catch( Throwable $e )
        {
            return $e->getMessage() ;
        }
    }

    /**
     * Prints a time of the failure.
     *
     * @param int|null $time
     *
     * @return string
     */
    private function date( ?int $time ) :string
    {
        return $time === null ? '?' : date( 'Y-m-d H:i' , $time ) ;
    }

    /**
     * Hides a push address, and its last segment, in a message.
     *
     * A failed connection reports the full address it tried: the message lands in a log, and a
     * log in an alert.
     *
     * @param string $message
     * @param string $url
     *
     * @return string
     */
    private function hide( string $message , string $url ) :string
    {
        $path  = parse_url( $url , PHP_URL_PATH ) ;
        $token = is_string( $path ) ? basename( $path ) : '' ;

        $message = str_replace( $url , $this->host( $url ) . '/' . self::HIDDEN , $message ) ;

        return $token === '' ? $message : str_replace( $token , self::HIDDEN , $message ) ;
    }

    /**
     * The scheme, host and port of a push address: all that may be printed of it.
     *
     * @param string $url
     *
     * @return string
     */
    private function host( string $url ) :string
    {
        $scheme = parse_url( $url , PHP_URL_SCHEME ) ;
        $host   = parse_url( $url , PHP_URL_HOST ) ;
        $port   = parse_url( $url , PHP_URL_PORT ) ;

        return ( is_string( $scheme ) ? $scheme : 'http' ) . '://' . ( is_string( $host ) ? $host : '?' ) . ( is_int( $port ) ? ':' . $port : '' ) ;
    }
}
