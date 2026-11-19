<?php

namespace oihana\commands\helpers;

/**
 * Stops a process and all its descendants: SIGTERM first, then SIGKILL to what is left.
 *
 * The descendants are listed before the first signal is sent. Once a parent is gone, its
 * children are handed to process 1 and can no longer be traced back to it.
 *
 * Each process is first asked to stop (SIGTERM), so that it may clean up after itself.
 * Those still running after `$grace` seconds are killed (SIGKILL), which cannot be ignored.
 * A process that has exited but not yet been collected by its parent (a zombie) counts as
 * stopped.
 *
 * The signals go through `posix_kill()` when the posix extension is loaded, and through the
 * `kill` command otherwise. A process this one may not signal (another user's, without
 * privileges) is left as it is.
 *
 * @param int   $pid   The process to stop, with all its descendants.
 * @param float $grace How long to wait, in seconds, between SIGTERM and SIGKILL.
 *
 * @return array<int> The processes still running after SIGKILL: normally none.
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\terminateProcessTree;
 *
 * $status = proc_get_status( $process ) ;
 * terminateProcessTree( $status[ 'pid' ] , grace: 5.0 ) ;
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
function terminateProcessTree( int $pid , float $grace = 5.0 ) :array
{
    $targets = [ ...processDescendants( $pid ) , $pid ] ;

    $signal = function( array $pids , int $signal ) :void
    {
        if( $pids === [] )
        {
            return ;
        }

        if( function_exists( 'posix_kill' ) )
        {
            foreach( $pids as $target )
            {
                @posix_kill( $target , $signal ) ;
            }

            return ;
        }

        // @codeCoverageIgnoreStart
        @shell_exec( sprintf( 'kill -%d %s 2>/dev/null' , $signal , implode( ' ' , array_map( 'intval' , $pids ) ) ) ) ;
        // @codeCoverageIgnoreEnd
    } ;

    $running = function() use ( &$targets ) :array
    {
        $table = processTable() ;

        return array_values( array_filter
        (
            $targets ,
            fn( int $target ) => isset( $table[ $target ] ) && !str_starts_with( $table[ $target ][ 'state' ] , 'Z' )
        ) ) ;
    } ;

    $signal( $targets , 15 ) ; // SIGTERM

    $deadline = microtime( true ) + max( 0.0 , $grace ) ;

    while( ( $left = $running() ) !== [] && microtime( true ) < $deadline )
    {
        usleep( 50_000 ) ;
    }

    if( $left === [] )
    {
        return [] ;
    }

    $signal( $left , 9 ) ; // SIGKILL

    // SIGKILL is delivered at once, but the table may still show the process for an instant.

    $deadline = microtime( true ) + 1.0 ;

    $targets = $left ;

    while( ( $left = $running() ) !== [] && microtime( true ) < $deadline )
    {
        usleep( 20_000 ) ;
    }

    return $left ;
}
