<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use InvalidArgumentException;

use oihana\commands\cron\CronJob;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CronJob: the checks of a job, its definition, its line.
 */
class CronJobTest extends TestCase
{
    public function testLineOfAnEnabledJob() :void
    {
        $job = new CronJob( 'check' , '0 6 * * 1-6' , '/usr/bin/php /srv/app/bin/console.php app:check' ) ;

        $this->assertSame
        (
            '0 6 * * 1-6 /usr/bin/php /srv/app/bin/console.php app:check # oihana-cron:myapp:check' ,
            $job->line( 'myapp' )
        ) ;
    }

    public function testLineOfADisabledJobIsCommentedOut() :void
    {
        $job = new CronJob( 'check' , '0 6 * * *' , '/srv/app/check.sh' , enabled: false ) ;

        $this->assertSame( '# 0 6 * * * /srv/app/check.sh # oihana-cron:myapp:check' , $job->line( 'myapp' ) ) ;
    }

    public function testLineAppendsTheOutputToTheLog() :void
    {
        $job = new CronJob( 'check' , '0 6 * * *' , '/srv/app/check.sh' , log: '/srv/app/logs/cron/check.log' ) ;

        $this->assertSame
        (
            "0 6 * * * /srv/app/check.sh >> '/srv/app/logs/cron/check.log' 2>&1 # oihana-cron:myapp:check" ,
            $job->line( 'myapp' )
        ) ;
    }

    public function testLineQuotesALogWithSpaces() :void
    {
        $job = new CronJob( 'check' , '@daily' , '/srv/app/check.sh' , log: '/srv/my app/check.log' ) ;

        $this->assertStringContainsString( ">> '/srv/my app/check.log' 2>&1" , $job->line( 'myapp' ) ) ;
    }

    public function testLineNormalizesTheSpacesOfTheSchedule() :void
    {
        $job = new CronJob( 'check' , '  0   6 *  * * ' , '  /srv/app/check.sh  ' ) ;

        $this->assertSame( '0 6 * * * /srv/app/check.sh # oihana-cron:myapp:check' , $job->line( 'myapp' ) ) ;
    }

    public function testLineRefusesAnInvalidScope() :void
    {
        $job = new CronJob( 'check' , '@daily' , '/srv/app/check.sh' ) ;

        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'The cron scope "my app" is not valid' ) ;

        $job->line( 'my app' ) ;
    }

    public function testAnEscapedPercentIsAccepted() :void
    {
        $job = new CronJob( 'stamp' , '@daily' , 'date +\%F > /srv/app/stamp' ) ;

        $this->assertStringContainsString( 'date +\%F' , $job->line( 'myapp' ) ) ;
    }

    /**
     * @return array<string, array{string, string, string, ?string, string}>
     */
    public static function invalidJobs() :array
    {
        return
        [
            'a name with a space'     => [ 'my job' , '@daily'      , '/srv/app/a.sh'    , null              , 'The cron name "my job" is not valid' ] ,
            'an empty name'           => [ ''       , '@daily'      , '/srv/app/a.sh'    , null              , 'The cron name "" is not valid' ] ,
            'an invalid schedule'     => [ 'job'    , '60 * * * *'  , '/srv/app/a.sh'    , null              , 'The schedule "60 * * * *" of the cron job "job" is not valid' ] ,
            'an empty command'        => [ 'job'    , '@daily'      , '   '              , null              , 'must be one line, not empty' ] ,
            'a command on two lines'  => [ 'job'    , '@daily'      , "/srv/a.sh\nrm x"  , null              , 'must be one line, not empty' ] ,
            'an unescaped percent'    => [ 'job'    , '@daily'      , 'date +%F'         , null              , 'holds an unescaped "%"' ] ,
            'a relative log'          => [ 'job'    , '@daily'      , '/srv/app/a.sh'    , 'logs/a.log'      , 'must be an absolute path' ] ,
            'a log with a percent'    => [ 'job'    , '@daily'      , '/srv/app/a.sh'    , '/srv/%F.log'     , 'must be an absolute path' ] ,
            'a log on two lines'      => [ 'job'    , '@daily'      , '/srv/app/a.sh'    , "/srv/a\n.log"    , 'must be an absolute path' ] ,
        ] ;
    }

    #[DataProvider( 'invalidJobs' )]
    public function testAnInvalidJobIsRefused( string $name , string $schedule , string $command , ?string $log , string $message ) :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( $message ) ;

        new CronJob( $name , $schedule , $command , log: $log ) ;
    }

    public function testFromArray() :void
    {
        $job = CronJob::fromArray
        ([
            CronJob::NAME     => 'check' ,
            CronJob::SCHEDULE => '*/5 * * * *' ,
            CronJob::COMMAND  => '/srv/app/check.sh' ,
            CronJob::ENABLED  => false ,
            CronJob::LOG      => '/srv/app/logs/check.log' ,
        ]) ;

        $this->assertSame  ( 'check'                   , $job->name     ) ;
        $this->assertSame  ( '*/5 * * * *'             , $job->schedule ) ;
        $this->assertSame  ( '/srv/app/check.sh'       , $job->command  ) ;
        $this->assertFalse ( $job->enabled ) ;
        $this->assertSame  ( '/srv/app/logs/check.log' , $job->log      ) ;
    }

    public function testFromArrayDefaults() :void
    {
        $job = CronJob::fromArray( [ 'name' => 'check' , 'schedule' => '@daily' , 'command' => '/srv/app/check.sh' , 'log' => '' ] ) ;

        $this->assertTrue ( $job->enabled ) ;
        $this->assertNull ( $job->log ) ;
    }

    public function testFromArrayRefusesAMissingKey() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'A cron job needs a "command" string' ) ;

        CronJob::fromArray( [ 'name' => 'check' , 'schedule' => '@daily' ] ) ;
    }

    public function testFromArrayRefusesANameThatIsNoString() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'A cron job needs a "name" string' ) ;

        CronJob::fromArray( [ 'name' => 12 , 'schedule' => '@daily' , 'command' => '/srv/app/check.sh' ] ) ;
    }
}
