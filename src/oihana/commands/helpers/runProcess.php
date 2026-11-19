<?php

namespace oihana\commands\helpers;

use oihana\commands\Process;
use RuntimeException;

/**
 * Runs a process, reads what it writes on both outputs, and stops it after a time limit.
 *
 * **Both outputs are read at the same time.** A process writing more than the pipe can hold
 * (about 64 KB) waits until someone reads it. Reading standard output to its end, then the
 * error output, deadlocks on a process with a lot to say on its error output: it waits for
 * the error pipe to be read, the caller waits for the end of standard output. Here both
 * pipes are watched together (`stream_select()`), and neither can fill up.
 *
 * **The time limit stops the whole tree.** A command given as a string runs through
 * `/bin/sh`, and most commands start their own children. Stopping only the process that was
 * started would leave its children running. When `$timeout` runs out, the process and all
 * its descendants get SIGTERM, then SIGKILL after `$grace` seconds
 * ({@see terminateProcessTree()}). The result then says `timedOut`, with the status 124 that
 * the `timeout` command of coreutils uses.
 *
 * **Standard input is `/dev/null`.** A process that asks a question reads an end of file and
 * goes on, or stops, instead of waiting for an answer nobody will give.
 *
 * A failing command does not raise: its status is in the result, for the caller to read.
 * Only a process that cannot be started at all raises.
 *
 * @param array|string $command  The command. A string runs through `/bin/sh -c`; an array
 *                               runs the program directly, each element one argument,
 *                               without a shell.
 * @param ?float       $timeout  The time limit, in seconds. Null or zero: no limit.
 * @param ?string      $cwd      The working directory of the process, or null for the
 *                               current one.
 * @param ?array       $env      The environment of the process, or null to inherit this one.
 * @param ?callable    $onOutput Receives standard output in chunks, as it comes. It is then
 *                               not kept: `output` stays empty. Use it for an output too big
 *                               to hold in memory.
 * @param float        $grace    How long, in seconds, the processes have between SIGTERM and
 *                               SIGKILL once the time limit has run out.
 *
 * @return Process The standard output and error output, untrimmed, the exit status, whether
 *                 the time limit ran out, and how long the process ran.
 *
 * @throws RuntimeException When the process cannot be started.
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\runProcess;
 *
 * $process = runProcess( [ 'tar' , '-tzf' , '/backups/site.tar.gz' ] , timeout: 600 ) ;
 *
 * if( $process->timedOut )
 * {
 *     // stopped after ten minutes, with everything tar had started
 * }
 * else if( $process->status !== 0 )
 * {
 *     echo $process->error ;
 * }
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
function runProcess
(
    array|string $command ,
    ?float       $timeout  = null ,
    ?string      $cwd      = null ,
    ?array       $env      = null ,
    ?callable    $onOutput = null ,
    float        $grace    = 5.0 ,
)
:Process
{
    $descriptors =
    [
        0 => [ 'file' , '/dev/null' , 'r' ] ,
        1 => [ 'pipe' , 'w' ] ,
        2 => [ 'pipe' , 'w' ] ,
    ] ;

    $started = microtime( true ) ;

    $process = @proc_open( $command , $descriptors , $pipes , $cwd , $env ) ;

    if( !is_resource( $process ) )
    {
        // The program alone, never its arguments: they may carry a password.

        $program = is_array( $command ) ? (string) ( $command[ array_key_first( $command ) ] ?? '' ) : (string) strtok( trim( $command ) , " \t" ) ;

        throw new RuntimeException( sprintf( 'Failed to execute the command `%s`.' , $program ) ) ;
    }

    stream_set_blocking( $pipes[ 1 ] , false ) ;
    stream_set_blocking( $pipes[ 2 ] , false ) ;

    $open     = [ 1 => $pipes[ 1 ] , 2 => $pipes[ 2 ] ] ;
    $output   = '' ;
    $error    = '' ;
    $timedOut = false ;
    $deadline = $timeout !== null && $timeout > 0 ? $started + $timeout : null ;

    while( $open !== [] )
    {
        $wait = null ;

        if( $deadline !== null )
        {
            $wait = $deadline - microtime( true ) ;

            if( $wait <= 0 )
            {
                $timedOut = true ;
                break ;
            }
        }

        $read   = array_values( $open ) ;
        $write  = null ;
        $except = null ;

        $ready = @stream_select
        (
            $read ,
            $write ,
            $except ,
            $wait === null ? null : (int) floor( $wait ) ,
            $wait === null ? null : (int) ( ( $wait - floor( $wait ) ) * 1_000_000 )
        ) ;

        // False when a signal interrupted the wait: nothing was read, the loop asks again.

        if( $ready === false || $ready === 0 )
        {
            continue ;
        }

        foreach( $read as $stream )
        {
            $key   = array_search( $stream , $open , true ) ;
            $chunk = fread( $stream , 65536 ) ;

            if( $chunk === false || ( $chunk === '' && feof( $stream ) ) )
            {
                fclose( $stream ) ;
                unset( $open[ $key ] ) ;
                continue ;
            }

            if( $key === 2 )
            {
                $error .= $chunk ;
            }
            else if( $onOutput !== null )
            {
                $onOutput( $chunk ) ;
            }
            else
            {
                $output .= $chunk ;
            }
        }
    }

    if( $timedOut )
    {
        $pid = (int) ( proc_get_status( $process )[ 'pid' ] ?? 0 ) ;

        if( $pid > 0 )
        {
            terminateProcessTree( $pid , $grace ) ;
        }

        foreach( $open as $stream )
        {
            fclose( $stream ) ;
        }

        proc_close( $process ) ;

        $status = 124 ;
    }
    else
    {
        $status = proc_close( $process ) ;
    }

    return new Process
    ([
        Process::OUTPUT    => $output ,
        Process::ERROR     => $error ,
        Process::STATUS    => $status ,
        Process::TIMED_OUT => $timedOut ,
        Process::SECONDS   => round( microtime( true ) - $started , 3 ) ,
    ]) ;
}
