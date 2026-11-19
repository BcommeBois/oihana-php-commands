<?php

namespace oihana\commands\helpers;

/**
 * Reads the process table: the parent and the state of every process on the machine.
 *
 * The table comes from `ps -A -o pid= -o ppid= -o stat=`, which reads the same on Linux
 * (procps) and on macOS (BSD `ps`). It can also be given as text in that same format, one
 * process per line, which is how the parsing is tested without real processes.
 *
 * A line that does not start with two numbers is skipped. The state column is optional: a
 * line without it gets an empty state.
 *
 * @param ?string $text The output of `ps -A -o pid= -o ppid= -o stat=`, or null to run it.
 *
 * @return array<int, array{ppid: int, state: string}> The processes, by process id. Empty
 *                                                     when `ps` could not be run.
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\processTable;
 *
 * $table = processTable( "  1     0 Ss\n 42     1 S+\n" ) ;
 * // [ 1 => [ 'ppid' => 0 , 'state' => 'Ss' ] , 42 => [ 'ppid' => 1 , 'state' => 'S+' ] ]
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
function processTable( ?string $text = null ) :array
{
    $text ??= (string) @shell_exec( 'ps -A -o pid= -o ppid= -o stat= 2>/dev/null' ) ;

    $table = [] ;

    foreach( preg_split( '/\R/' , $text ) ?: [] as $line )
    {
        if( preg_match( '/^\s*(\d+)\s+(\d+)(?:\s+(\S+))?/' , $line , $matches ) !== 1 )
        {
            continue ;
        }

        $table[ (int) $matches[ 1 ] ] = [ 'ppid' => (int) $matches[ 2 ] , 'state' => $matches[ 3 ] ?? '' ] ;
    }

    return $table ;
}
