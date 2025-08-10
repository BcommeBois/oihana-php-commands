<?php

namespace oihana\commands\traits;

use oihana\commands\enums\CommandParam;
use RuntimeException;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\SudoCommandOptions;
use oihana\commands\options\CommandOptions;
use oihana\commands\Process;

use function oihana\commands\helpers\makeCommand;
use function oihana\commands\helpers\silent;

/**
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait CommandTrait
{
    /**
     * The global command options.
     * @var ?CommandOptions
     */
    public ?CommandOptions $commandOptions = null ;

    /**
     * Initialize the global command options.
     * @param array $init
     * @return static
     */
    protected function initializeCommandOptions( array $init = [] ) :static
    {
        $this->commandOptions = CommandOptions::create( $init[ CommandParam::COMMAND ] ?? $init ) ;
        return $this ;
    }

    /**
     * Executes a shell command with optional sudo/user context and silent mode and returns the output of the command.
     *
     * @param null|array|string   $command  The base shell command to execute (e.g. "wp plugin install").
     * @param null|array|string   $args     Optional additional arguments appended to the command, a string or an array of arguments.
     * @param CommandOptions|null $options  Whether to prefix the command with `sudo`. Defaults to `$this->sudo`.
     * @param bool                $silent   Whether to suppress the command's output.
     * @param bool                $verbose  Verbose the error message.
     * @param ?string             $previous The previous command to append.
     * @param ?string             $post     The post command to append at the end.
     * @param bool $sudo If true, the command is automatically prefixed with `sudo` to run with elevated privileges.
     * @param bool $dryRun If true, simulates the execution without actually running the command. Always returns 0.
     *
     * @return string The output of the command.
     *
     * @throws RuntimeException If the command returned no output.
     *
     * @example
     * ```php
     * $this->system('wp theme install hello-elementor', '--path=/var/www/site' );
     * $this->system('ls', '-la', new CommandOptions([ 'sudo' => true, 'user' => 'www-data');
     * ```
     */
    public function exec
    (
        null|array|string $command ,
        null|array|string $args     = null  ,
        ?CommandOptions   $options  = null  ,
        bool              $silent   = false ,
        bool              $verbose  = false ,
        ?string           $previous = null  ,
        ?string           $post     = null  ,
        bool              $sudo     = false ,
        bool              $dryRun   = false ,

    )
    :string
    {
        $options     = $sudo ? new SudoCommandOptions() : CommandOptions::resolve( $this->commandOptions , $options ) ;
        $fullCommand = makeCommand( $command , $args , $options , $previous , $post ) ;

        if( $verbose )
        {
            $this->info( '[▶] exec: ' . $fullCommand  . PHP_EOL ) ;
        }

        if ( $silent )
        {
            $fullCommand .= ' 2>&1'; // Capture aussi stderr dans stdout
        }

        $output = shell_exec( $fullCommand );

        if( $output === null )
        {
            throw new RuntimeException( sprintf( 'The command `%s` returned no output.' , $verbose ? $fullCommand : $command ) ) ;
        }

        return trim( $output ) ;
    }

    /**
     * Executes a shell command using proc_open, capturing stdout, stderr, and the exit status.
     *
     * This method builds a full command from the base command, optional arguments, and optional sudo/user context.
     * It provides full control over the execution environment and captures both standard output and error output separately.
     *
     * @param null|array|string   $command   The base shell command to execute (e.g. "wp plugin list").
     * @param null|array|string   $args      Optional arguments to pass to the command. Can be a string or array.
     * @param CommandOptions|null $options   Optional command options, such as sudo or user context.
     * @param bool                $verbose   Whether to display the full command before execution.
     * @param ?string             $previous  The previous command to append.
     * @param ?string             $post      The post command to append at the end.
     * @param bool                $sudo      If true, the command is automatically prefixed with `sudo` to run with elevated privileges.
     * @param bool                $dryRun    If true, simulates the execution without actually running the command. Always returns 0.
     *
     * @return Process
     *
     * @throws RuntimeException If the process could not be started.
     *
     * @example
     * ```php
     * $result = $this->proc( 'wp post list' , ' --format=ids') ;
     * if ( $result->status === 0 )
     * {
     *     $ids = explode( PHP_EOL , $result->output ) ;
     * }
     * ```
     */
    public function proc
    (
        null|array|string $command ,
        null|array|string $args     = null  ,
        ?CommandOptions   $options  = null  ,
        bool              $verbose  = false ,
        ?string           $previous = null  ,
        ?string           $post     = null  ,
        bool              $sudo     = false ,
        bool              $dryRun   = false ,
    )
    :Process
    {
        $options = $sudo ? new SudoCommandOptions() : CommandOptions::resolve( $this->commandOptions , $options ) ;

        $fullCommand = makeCommand( $command , $args , $options , $previous , $post ) ;

        if ( $verbose )
        {
            $this->info('[▶] proc: ' . $fullCommand . PHP_EOL ) ;
        }

        if( $dryRun )
        {
            return new Process
            ([
                Process::OUTPUT => "<do nothing>" ,
                Process::ERROR  => "" ,
                Process::STATUS => ExitCode::SUCCESS
            ]) ;
        }

        $descriptor =
        [
            0 => [ 'pipe' , 'r' ] , // stdin (unused)
            1 => [ 'pipe' , 'w' ] , // stdout
            2 => [ 'pipe' , 'w' ] , // stderr
        ];

        $process = proc_open( $fullCommand , $descriptor , $pipes ) ;

        if ( !is_resource( $process ) )
        {
            throw new RuntimeException( sprintf( "Failed to execute the command `%s`." ,$verbose ? $fullCommand : $command ) );
        }

        fclose( $pipes[0] ) ; // stdin

        $stdout = stream_get_contents( $pipes[1] ) ;
        fclose( $pipes[1] );

        $stderr = stream_get_contents( $pipes[2] );
        fclose( $pipes[2] ) ;

        $exitCode = proc_close($process);

        return new Process
        ([
            Process::OUTPUT => trim( (string) $stdout ) ,
            Process::ERROR  => trim( (string) $stderr ) ,
            Process::STATUS => $exitCode,
        ]);
    }

    /**
     * Executes a shell command with optional sudo/user context and silent mode.
     *
     * This method builds and executes a shell command string, optionally prefixed with `sudo -u <user>`.
     * It can suppress output using the `makeSilent()` method and throws an exception on failure.
     *
     * @param null|array|string         $command     The base shell command to execute (e.g. "wp plugin install").
     * @param null|array|string         $args        Optional additional arguments appended to the command, a string or an array of arguments.
     * @param null|array|CommandOptions $options     Whether to prefix the command with `sudo`. Defaults to `$this->sudo`.
     * @param bool                      $silent      Whether to suppress the command's output.
     * @param bool                      $verbose     Verbose the error message.
     * @param ?string                   $previous    Optional command to prepend (e.g., `cd /path &&`).
     * @param ?string                   $post        Optional command to append after execution (e.g., `; echo done`).
     * @param bool                      $sudo        If true, the command is automatically prefixed with `sudo` to run with elevated privileges.
     * @param bool                      $dryRun      If true, simulates the execution without actually running the command. Always returns 0.
     *
     * @return int The result code of the command (0 if successful).
     *
     * @throws RuntimeException RuntimeException If the command fails and `$dryRun` is false.
     *
     * @example
     * ```php
     * $this->system('wp theme install hello-elementor', '--path=/var/www/site' );
     * $this->system('ls', '-la', new CommandOptions([ 'sudo' => true, 'user' => 'www-data');
     * $this->system('rm -rf /tmp/sandbox', verbose:true, dryRun: true); // dry-run, shows command
     * ```
     */
    public function system
    (
        null|array|string         $command ,
        null|array|string         $args     = null  ,
        null|array|CommandOptions $options  = null  ,
        bool                      $silent   = false ,
        bool                      $verbose  = false ,
       ?string                    $previous = null  ,
       ?string                    $post     = null  ,
        bool                      $sudo     = false ,
        bool                      $dryRun   = false ,
    )
    : int
    {
        $options = $sudo ? new SudoCommandOptions() : CommandOptions::resolve( $this->commandOptions , $options ) ;

        $fullCommand = makeCommand( $command , $args , $options , $previous , $post ) ;

        if( $verbose )
        {
            $this->info( sprintf( '[▶] system: %s' , $fullCommand ) . PHP_EOL ) ;
        }

        if( $dryRun )
        {
            return ExitCode::SUCCESS ;
        }

        system( silent( $fullCommand , $silent ) , $status ) ;

        if( $status == ExitCode::SUCCESS )
        {
            return $status ;
        }
        else
        {
            throw new RuntimeException
            (
                sprintf( 'The command %s failed.' , $fullCommand ) ,
                $status
            ) ;
        }
    }
}