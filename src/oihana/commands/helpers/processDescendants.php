<?php

namespace oihana\commands\helpers;

/**
 * Lists the descendants of a process: its children, their children, and so on.
 *
 * The deepest come first, so that stopping them in this order never leaves a parent
 * running long enough to start a new child in place of one just stopped.
 *
 * The process itself is not in the list. A process that left its parent (a daemon, which
 * forks and lets its parent exit) is no longer anyone's descendant, and is not found.
 *
 * @param int    $pid   The process whose descendants are wanted.
 * @param ?array $table The process table, from {@see processTable()}; null to read it now.
 *
 * @return array<int> The process ids, the deepest first. Empty when the process has none.
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\processDescendants;
 * use function oihana\commands\helpers\processTable;
 *
 * $table = processTable( "10 1 S\n11 10 S\n12 11 S\n13 10 S\n" ) ;
 *
 * processDescendants( 10 , $table ) ; // [ 12 , 11 , 13 ]
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
function processDescendants( int $pid , ?array $table = null ) :array
{
    $table ??= processTable() ;

    $children = [] ;

    foreach( $table as $child => $row )
    {
        $children[ (int) ( $row[ 'ppid' ] ?? 0 ) ][] = (int) $child ;
    }

    $found   = [] ;
    $visited = [ $pid => true ] ;

    $walk = function( int $parent ) use ( &$walk , &$found , &$visited , $children ) :void
    {
        foreach( $children[ $parent ] ?? [] as $child )
        {
            if( isset( $visited[ $child ] ) )
            {
                continue ;
            }

            $visited[ $child ] = true ;

            $walk( $child ) ;

            $found[] = $child ;
        }
    } ;

    $walk( $pid ) ;

    return $found ;
}
