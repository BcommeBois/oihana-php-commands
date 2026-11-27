<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use oihana\commands\cron\CronSchedule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CronSchedule::isValid().
 */
class CronScheduleTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validSchedules() :array
    {
        return
        [
            'every minute'                 => [ '* * * * *' ] ,
            'every five minutes'           => [ '*/5 * * * *' ] ,
            'a list of hours, a day range' => [ '0 6,8,10,12,14,16,18 * * 1-6' ] ,
            'a range with a step'          => [ '30 5-21/2 * * *' ] ,
            'names, in any case'           => [ '0 0 1 JAN mon-Fri' ] ,
            'sunday as 7'                  => [ '0 0 * * 7' ] ,
            'the highest values'           => [ '59 23 31 12 7' ] ,
            'the lowest values'            => [ '0 0 1 1 0' ] ,
            'spaces around and between'    => [ '  0   6 * * *  ' ] ,
            'a macro'                      => [ '@daily' ] ,
            'a macro in capitals'          => [ '@HOURLY' ] ,
            'the reboot macro'             => [ '@reboot' ] ,
        ] ;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSchedules() :array
    {
        return
        [
            'empty'                    => [ '' ] ,
            'four fields'              => [ '* * * *' ] ,
            'six fields'               => [ '* * * * * *' ] ,
            'minute 60'                => [ '60 * * * *' ] ,
            'hour 24'                  => [ '* 24 * * *' ] ,
            'day of the month 0'       => [ '* * 0 * *' ] ,
            'day of the month 32'      => [ '* * 32 * *' ] ,
            'month 0'                  => [ '* * * 0 *' ] ,
            'month 13'                 => [ '* * * 13 *' ] ,
            'day of the week 8'        => [ '* * * * 8' ] ,
            'a range backwards'        => [ '5-1 * * * *' ] ,
            'a range of three bounds'  => [ '1-2-3 * * * *' ] ,
            'a step of zero'           => [ '*/0 * * * *' ] ,
            'a step that is no number' => [ '*/x * * * *' ] ,
            'two steps'                => [ '*/5/2 * * * *' ] ,
            'a step on a single value' => [ '5/10 * * * *' ] ,
            'an empty list item'       => [ '1,,2 * * * *' ] ,
            'a word'                   => [ 'a * * * *' ] ,
            'a month name as a day'    => [ '* * * * jan' ] ,
            'a day name as a month'    => [ '* * * mon *' ] ,
            'a negative value'         => [ '-1 * * * *' ] ,
            'an unknown macro'         => [ '@sometimes' ] ,
            'a macro with fields'      => [ '@daily *' ] ,
        ] ;
    }

    #[DataProvider( 'validSchedules' )]
    public function testValidSchedule( string $schedule ) :void
    {
        $this->assertTrue( CronSchedule::isValid( $schedule ) ) ;
    }

    #[DataProvider( 'invalidSchedules' )]
    public function testInvalidSchedule( string $schedule ) :void
    {
        $this->assertFalse( CronSchedule::isValid( $schedule ) ) ;
    }
}
