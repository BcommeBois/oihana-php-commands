<?php

namespace oihana\commands\cron;

/**
 * Checks a cron schedule before it reaches a crontab.
 *
 * A schedule is either one of the macros cron knows (`@daily`, `@hourly`…) or five fields:
 * minute, hour, day of the month, month and day of the week. Each field is a list, separated
 * by commas, of `*`, a value or a range (`1-5`), each optionally followed by a step (`8-18/2`). Months and days of the week also take their three-letter English names
 * (`jan`, `mon`), in any case; Sunday is `0` or `7`.
 *
 * The check is stricter than some cron implementations: a step on a single value (`5/10`)
 * and a range written backwards (`5-1`) are refused, since cron daemons read them differently.
 * A schedule refused here is written another way; a schedule accepted here means the same
 * thing to every cron.
 *
 * @example
 * ```php
 * use oihana\commands\cron\CronSchedule;
 *
 * CronSchedule::isValid( '0 8-18/2 * * 1-5' ) ; // true
 * CronSchedule::isValid( '0 6,8,10 * * mon-sat' ) ; // true
 * CronSchedule::isValid( '60 * * * *'  ) ; // false — a minute goes up to 59
 * ```
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final class CronSchedule
{
    /**
     * The lowest and highest value of each of the five fields.
     */
    public const array BOUNDS =
    [
        [ 0 , 59 ] , // minute
        [ 0 , 23 ] , // hour
        [ 1 , 31 ] , // day of the month
        [ 1 , 12 ] , // month
        [ 0 ,  7 ] , // day of the week, 0 and 7 are Sunday
    ] ;

    /**
     * The macros cron reads in place of the five fields.
     */
    public const array MACROS =
    [
        '@annually' ,
        '@daily'    ,
        '@hourly'   ,
        '@midnight' ,
        '@monthly'  ,
        '@reboot'   ,
        '@weekly'   ,
        '@yearly'   ,
    ] ;

    /**
     * The names a field takes in place of a number, by the position of the field.
     */
    public const array NAMES =
    [
        3 =>
        [
            'jan' => 1 , 'feb' => 2 , 'mar' =>  3 , 'apr' =>  4 , 'may' =>  5 , 'jun' =>  6 ,
            'jul' => 7 , 'aug' => 8 , 'sep' =>  9 , 'oct' => 10 , 'nov' => 11 , 'dec' => 12 ,
        ] ,
        4 =>
        [
            'sun' => 0 , 'mon' => 1 , 'tue' => 2 , 'wed' => 3 , 'thu' => 4 , 'fri' => 5 , 'sat' => 6 ,
        ] ,
    ] ;

    /**
     * Tells whether a schedule is one cron reads the same way everywhere.
     *
     * @param string $schedule The schedule: a macro, or five fields separated by spaces.
     *
     * @return bool True when the schedule can be written in a crontab.
     */
    public static function isValid( string $schedule ) :bool
    {
        $schedule = trim( $schedule ) ;

        if( in_array( strtolower( $schedule ) , self::MACROS , true ) )
        {
            return true ;
        }

        $fields = preg_split( '/\s+/' , $schedule ) ;

        if( $fields === false || count( $fields ) !== count( self::BOUNDS ) )
        {
            return false ;
        }

        foreach( $fields as $position => $field )
        {
            foreach( explode( ',' , $field ) as $item )
            {
                if( !self::isItem( $item , $position ) )
                {
                    return false ;
                }
            }
        }

        return true ;
    }

    /**
     * Tells whether one item of a field list is valid: `*`, a value or a range, with an optional step.
     *
     * @param string $item     The item, between two commas.
     * @param int    $position The position of its field, from 0 (minute) to 4 (day of the week).
     *
     * @return bool True when the item is valid for its field.
     */
    private static function isItem( string $item , int $position ) :bool
    {
        $parts = explode( '/' , $item ) ;

        if( count( $parts ) > 2 )
        {
            return false ;
        }

        $range = $parts[ 0 ] ;

        if( count( $parts ) === 2 )
        {
            $step = $parts[ 1 ] ;

            if( !ctype_digit( $step ) || (int) $step === 0 )
            {
                return false ;
            }

            if( $range !== '*' && !str_contains( $range , '-' ) )
            {
                return false ;
            }
        }

        if( $range === '*' )
        {
            return true ;
        }

        $bounds = explode( '-' , $range ) ;

        if( count( $bounds ) > 2 )
        {
            return false ;
        }

        [ $min , $max ] = self::BOUNDS[ $position ] ;

        $values = [] ;

        foreach( $bounds as $bound )
        {
            $value = self::value( $bound , $position ) ;

            if( $value === null || $value < $min || $value > $max )
            {
                return false ;
            }

            $values[] = $value ;
        }

        return count( $values ) === 1 || $values[ 0 ] <= $values[ 1 ] ;
    }

    /**
     * The number a bound stands for: its digits, or the number of its name.
     *
     * @param string $bound    A bound of a range, or a single value.
     * @param int    $position The position of its field.
     *
     * @return int|null The number, or null when the bound is neither digits nor a name of its field.
     */
    private static function value( string $bound , int $position ) :?int
    {
        if( ctype_digit( $bound ) )
        {
            return (int) $bound ;
        }

        return self::NAMES[ $position ][ strtolower( $bound ) ] ?? null ;
    }
}
