<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use InvalidArgumentException;

use oihana\commands\cron\CronChange;
use oihana\commands\cron\CronJob;
use oihana\commands\cron\CrontabPlan;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CrontabPlan: what a crontab must become so that a scope matches its jobs,
 * without ever touching a line that is not of the scope.
 */
class CrontabPlanTest extends TestCase
{
    private const string SCOPE = 'myapp-staging' ;

    /**
     * A crontab shared by two instances of an application and another tool.
     *
     * @return list<string>
     */
    private function crontab() :array
    {
        return
        [
            '# Set PATH so cron can find the usual binaries' ,
            'PATH=/usr/local/bin:/usr/bin:/bin' ,
            '' ,
            '# The report of another tool' ,
            '0 2 * * * /srv/other/report.sh' ,
            '' ,
            '0 5 * * 1-6 /srv/app/bin/import.sh # oihana-cron:myapp-production:import' ,
            '*/5 * * * * /usr/bin/php /srv/app-staging/bin/console.php app:check # oihana-cron:myapp-staging:check' ,
            '' ,
            '# Debug: add >> /home/user/cron.log 2>&1' ,
        ] ;
    }

    private function check( bool $enabled = true , string $schedule = '*/5 * * * *' ) :CronJob
    {
        return new CronJob( 'check' , $schedule , '/usr/bin/php /srv/app-staging/bin/console.php app:check' , $enabled ) ;
    }

    public function testAMatchingCrontabHoldsNoChange() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check() ] , $this->crontab() ) ;

        $this->assertTrue  ( $plan->isEmpty() ) ;
        $this->assertSame  ( [] , $plan->changes ) ;
        $this->assertSame  ( $this->crontab() , $plan->lines ) ;
        $this->assertNull  ( $plan->change( 'check' ) ) ;
    }

    public function testAJobIsAddedAfterTheLastLineOfTheScope() :void
    {
        $report = new CronJob( 'report' , '0 7 * * *' , '/srv/app-staging/bin/report.sh' ) ;
        $plan   = new CrontabPlan( self::SCOPE , [ $this->check() , $report ] , $this->crontab() ) ;

        $expected = $this->crontab() ;
        array_splice( $expected , 8 , 0 , [ '0 7 * * * /srv/app-staging/bin/report.sh # oihana-cron:myapp-staging:report' ] ) ;

        $this->assertSame( $expected , $plan->lines ) ;
        $this->assertEquals
        (
            [ new CronChange( CronChange::ADD , 'report' , null , $expected[ 8 ] ) ] ,
            $plan->changes
        ) ;
    }

    public function testAJobIsAddedAtTheEndWhenTheScopeHasNoLine() :void
    {
        $job  = new CronJob( 'check' , '@hourly' , '/srv/new/check.sh' ) ;
        $plan = new CrontabPlan( 'newapp' , [ $job ] , $this->crontab() ) ;

        $this->assertSame( [ ...$this->crontab() , '@hourly /srv/new/check.sh # oihana-cron:newapp:check' ] , $plan->lines ) ;
        $this->assertSame( CronChange::ADD , $plan->change( 'check' )?->type ) ;
    }

    public function testAJobIsAddedToAnEmptyCrontab() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check() ] , [] ) ;

        $this->assertSame( [ $this->check()->line( self::SCOPE ) ] , $plan->lines ) ;
        $this->assertSame( CronChange::ADD , $plan->change( 'check' )?->type ) ;
    }

    public function testANewScheduleUpdatesTheLineInPlace() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check( schedule: '*/10 * * * *' ) ] , $this->crontab() ) ;

        $expected      = $this->crontab() ;
        $expected[ 7 ] = '*/10 * * * * /usr/bin/php /srv/app-staging/bin/console.php app:check # oihana-cron:myapp-staging:check' ;

        $this->assertSame( $expected , $plan->lines ) ;
        $this->assertEquals
        (
            [ new CronChange( CronChange::UPDATE , 'check' , $this->crontab()[ 7 ] , $expected[ 7 ] ) ] ,
            $plan->changes
        ) ;
    }

    public function testANewCommandUpdatesTheLine() :void
    {
        $job  = new CronJob( 'check' , '*/5 * * * *' , '/usr/bin/php /srv/app-staging/bin/console.php app:check --apply' ) ;
        $plan = new CrontabPlan( self::SCOPE , [ $job ] , $this->crontab() ) ;

        $this->assertSame( CronChange::UPDATE , $plan->change( 'check' )?->type ) ;
        $this->assertSame( $job->line( self::SCOPE ) , $plan->lines[ 7 ] ) ;
    }

    public function testADisabledJobCommentsItsLineOut() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check( enabled: false ) ] , $this->crontab() ) ;

        $this->assertSame( CronChange::DISABLE , $plan->change( 'check' )?->type ) ;
        $this->assertSame( '# ' . $this->crontab()[ 7 ] , $plan->lines[ 7 ] ) ;
    }

    public function testAnEnabledJobUncommentsItsLine() :void
    {
        $lines      = $this->crontab() ;
        $lines[ 7 ] = '# ' . $lines[ 7 ] ;

        $plan = new CrontabPlan( self::SCOPE , [ $this->check() ] , $lines ) ;

        $this->assertSame( CronChange::ENABLE , $plan->change( 'check' )?->type ) ;
        $this->assertSame( $this->crontab() , $plan->lines ) ;
    }

    public function testADisabledJobWithANewScheduleIsAnUpdate() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check( false , '@hourly' ) ] , $this->crontab() ) ;

        $this->assertSame( CronChange::UPDATE , $plan->change( 'check' )?->type ) ;
        $this->assertStringStartsWith( '# @hourly ' , $plan->lines[ 7 ] ) ;
    }

    public function testALineNoJobIsDefinedForIsRemoved() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [] , $this->crontab() ) ;

        $expected = $this->crontab() ;
        array_splice( $expected , 7 , 1 ) ;

        $this->assertSame( $expected , $plan->lines ) ;
        $this->assertEquals
        (
            [ new CronChange( CronChange::REMOVE , 'check' , $this->crontab()[ 7 ] ) ] ,
            $plan->changes
        ) ;
    }

    public function testASecondLineOfTheSameJobIsRemoved() :void
    {
        $lines   = $this->crontab() ;
        $lines[] = '0 0 * * * /old/check.sh # oihana-cron:myapp-staging:check' ;

        $plan = new CrontabPlan( self::SCOPE , [ $this->check() ] , $lines ) ;

        $this->assertSame( $this->crontab() , $plan->lines ) ;
        $this->assertSame( [ 'check' => $this->crontab()[ 7 ] ] , $plan->installed ) ;
        $this->assertCount( 1 , $plan->changes ) ;
        $this->assertSame( CronChange::REMOVE , $plan->changes[ 0 ]->type ) ;
    }

    public function testTheLinesOfAnotherScopeAndUnmarkedLinesStayInPlace() :void
    {
        $job  = new CronJob( 'import' , '@daily' , '/srv/app-staging/bin/import.sh' ) ;
        $plan = new CrontabPlan( self::SCOPE , [ $job ] , $this->crontab() ) ;

        foreach( [ 0 , 1 , 2 , 3 , 4 , 5 , 6 ] as $index )
        {
            $this->assertSame( $this->crontab()[ $index ] , $plan->lines[ $index ] ) ;
        }

        // The scope keeps no line of its own: the new job goes at the end of the crontab.

        $this->assertSame( [ '' , '# Debug: add >> /home/user/cron.log 2>&1' , $job->line( self::SCOPE ) ] , array_slice( $plan->lines , 7 ) ) ;
        $this->assertSame( CronChange::REMOVE , $plan->changes[ 0 ]->type ) ;
        $this->assertSame( 'check' , $plan->changes[ 0 ]->name ) ;
        $this->assertSame( CronChange::ADD , $plan->changes[ 1 ]->type ) ;
    }

    public function testAPlanOnTheLinesOfAPlanHoldsNoChange() :void
    {
        $jobs =
        [
            $this->check( false ) ,
            new CronJob( 'report' , '0 7 * * *' , '/srv/app-staging/bin/report.sh' , log: '/srv/app-staging/logs/report.log' ) ,
        ] ;

        $first  = new CrontabPlan( self::SCOPE , $jobs , $this->crontab() ) ;
        $second = new CrontabPlan( self::SCOPE , $jobs , $first->lines ) ;

        $this->assertFalse ( $first->isEmpty() ) ;
        $this->assertTrue  ( $second->isEmpty() ) ;
        $this->assertSame  ( $first->lines , $second->lines ) ;
    }

    public function testTwoJobsWithTheSameNameAreRefused() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'Two cron jobs of the scope "myapp-staging" are named "check"' ) ;

        new CrontabPlan( self::SCOPE , [ $this->check() , $this->check() ] , [] ) ;
    }

    public function testAnInvalidScopeIsRefused() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'The cron scope "my:app" is not valid' ) ;

        new CrontabPlan( 'my:app' , [] , [] ) ;
    }

    public function testTheJobsAreIndexedByName() :void
    {
        $plan = new CrontabPlan( self::SCOPE , [ $this->check() ] , [] ) ;

        $this->assertSame( [ 'check' ] , array_keys( $plan->jobs ) ) ;
        $this->assertSame( self::SCOPE , $plan->scope ) ;
    }
}
