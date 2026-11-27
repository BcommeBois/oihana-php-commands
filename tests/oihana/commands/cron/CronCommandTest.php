<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use DI\Container;

use oihana\commands\cron\CronCommand;
use oihana\commands\cron\CronJob;
use oihana\commands\enums\CronParam;
use oihana\commands\enums\ExitCode;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * Unit tests for CronCommand: each action, against a crontab played in memory.
 */
class CronCommandTest extends TestCase
{
    private const string OTHER = '0 2 * * * /srv/other/report.sh' ;

    private const string CHECK = '*/5 * * * * /srv/app/check.sh # oihana-cron:myapp:check' ;

    private string $backups ;

    protected function setUp() :void
    {
        $this->backups = sys_get_temp_dir() . '/oihana-cron-command-test-' . bin2hex( random_bytes( 4 ) ) ;
    }

    protected function tearDown() :void
    {
        foreach( glob( $this->backups . '/*' ) ?: [] as $file )
        {
            unlink( $file ) ;
        }

        if( is_dir( $this->backups ) )
        {
            rmdir( $this->backups ) ;
        }
    }

    /**
     * @param FakeCrontabRunner          $fake The crontab.
     * @param array<int, mixed>|null     $jobs The jobs, the `check` job by default.
     * @param array<string, mixed>       $init More initialization parameters.
     */
    private function tester( FakeCrontabRunner $fake , ?array $jobs = null , array $init = [] ) :CommandTester
    {
        $command = new CronCommand
        (
            'test:cron' ,
            new Container() ,
            [
                CronParam::SCOPE   => 'myapp' ,
                CronParam::JOBS    => $jobs ?? [ [ 'name' => 'check' , 'schedule' => '*/5 * * * *' , 'command' => '/srv/app/check.sh' ] ] ,
                CronParam::BACKUPS => $this->backups ,
                CronParam::CRONTAB => $fake->crontab() ,
                ...$init ,
            ]
        ) ;

        return new CommandTester( $command ) ;
    }

    public function testListShowsTheJobsAndWhatSyncWouldDo() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" . '0 0 * * * /old.sh # oihana-cron:myapp:old' . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [] ) ) ;

        $display = $tester->getDisplay() ;

        $this->assertStringContainsString( 'Scope: myapp' , $display ) ;
        $this->assertMatchesRegularExpression( '/check\s+\*\/5 \* \* \* \*\s+enabled\s+missing\s+adds it/' , $display ) ;
        $this->assertMatchesRegularExpression( '/old\s+not declared\s+enabled\s+removes it/' , $display ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }

    public function testListOfAnUpToDateCrontab() :void
    {
        $fake   = new FakeCrontabRunner( '# ' . self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake , [ new CronJob( 'check' , '*/5 * * * *' , '/srv/app/check.sh' , false ) ] ) ;

        $tester->execute( [ 'action' => 'list' ] ) ;

        $this->assertMatchesRegularExpression( '/check\s+\S.*\s+disabled\s+disabled\s+up to date/' , $tester->getDisplay() ) ;
    }

    public function testListWithoutJobOrLine() :void
    {
        $tester = $this->tester( new FakeCrontabRunner( self::OTHER . "\n" ) , [] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'list' ] ) ) ;
        $this->assertStringContainsString( 'No job is declared' , $tester->getDisplay() ) ;
    }

    public function testSyncWithoutApplyWritesNothing() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'sync' ] ) ) ;

        $display = $tester->getDisplay() ;

        $this->assertStringContainsString( 'add check' , $display ) ;
        $this->assertStringContainsString( '+ ' . self::CHECK , $display ) ;
        $this->assertStringContainsString( 'Nothing was written' , $display ) ;
        $this->assertSame( 0 , $fake->writes ) ;
        $this->assertDirectoryDoesNotExist( $this->backups ) ;
    }

    public function testSyncApplyCopiesThenWrites() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'sync' , '--apply' => true ] ) ) ;

        $this->assertSame( self::OTHER . "\n" . self::CHECK . "\n" , $fake->text ) ;

        $copies = glob( $this->backups . '/crontab-*.bak' ) ?: [] ;

        $this->assertCount( 1 , $copies ) ;
        $this->assertSame( self::OTHER . "\n" , file_get_contents( $copies[ 0 ] ) ) ;
        $this->assertStringContainsString( '1 change(s) written' , $tester->getDisplay() ) ;
    }

    public function testSyncApplyWithoutBackupDirectory() :void
    {
        $fake   = new FakeCrontabRunner() ;
        $tester = $this->tester( $fake , init: [ CronParam::BACKUPS => null ] ) ;

        $tester->execute( [ 'action' => 'sync' , '--apply' => true ] ) ;

        $this->assertSame( self::CHECK . "\n" , $fake->text ) ;
        $this->assertStringContainsString( 'No backup directory is set' , $tester->getDisplay() ) ;
        $this->assertDirectoryDoesNotExist( $this->backups ) ;
    }

    public function testSyncShowsTheLineBeforeAndAfter() :void
    {
        $fake   = new FakeCrontabRunner( '0 6 * * * /srv/app/check.sh # oihana-cron:myapp:check' . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $tester->execute( [ 'action' => 'sync' ] ) ;

        $display = $tester->getDisplay() ;

        $this->assertStringContainsString( 'update check' , $display ) ;
        $this->assertStringContainsString( '- 0 6 * * * /srv/app/check.sh # oihana-cron:myapp:check' , $display ) ;
        $this->assertStringContainsString( '+ ' . self::CHECK , $display ) ;
    }

    public function testAWriteWaitsForNoOtherWrite() :void
    {
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore() ;
        $lock  = new LockFactory( $store )->createLock( CronCommand::LOCK ) ;

        $this->assertTrue( $lock->acquire() ) ;

        try
        {
            $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
            $tester = $this->tester( $fake ) ;

            $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'sync' , '--apply' => true ] ) ) ;
            $this->assertStringContainsString( 'already running' , $tester->getDisplay() ) ;
            $this->assertSame( 0 , $fake->writes ) ;

            $this->assertSame( ExitCode::SUCCESS , $this->tester( $fake )->execute( [ 'action' => 'list' ] ) ) ;
        }
        finally
        {
            $lock->release() ;
        }
    }

    public function testSyncCheckEndsOnOneWhenTheCrontabIsNotUpToDate() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'sync' , '--check' => true ] ) ) ;
        $this->assertStringContainsString( 'add check' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
        $this->assertDirectoryDoesNotExist( $this->backups ) ;
    }

    public function testSyncCheckEndsOnZeroWhenTheCrontabIsUpToDate() :void
    {
        $fake   = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'sync' , '--check' => true ] ) ) ;
        $this->assertStringContainsString( 'The crontab is up to date' , $tester->getDisplay() ) ;
    }

    public function testSyncCheckEndsOnOneWhenTheCrontabCannotBeRead() :void
    {
        $fake              = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $fake->readFailure = [ 'crontab: permission denied' , 1 ] ;

        $this->assertSame( ExitCode::FAILURE , $this->tester( $fake )->execute( [ 'action' => 'sync' , '--check' => true ] ) ) ;
    }

    public function testCheckWithApplyIsInvalidAndWritesNothing() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::INVALID , $tester->execute( [ 'action' => 'sync' , '--check' => true , '--apply' => true ] ) ) ;
        $this->assertStringContainsString( 'cannot go together' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
        $this->assertSame( [] , $fake->calls ) ;
    }

    public function testSyncOfAnUpToDateCrontab() :void
    {
        $fake   = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'sync' , '--apply' => true ] ) ) ;
        $this->assertStringContainsString( 'The crontab is up to date' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }

    public function testDisableCommentsTheLineOutAndWarnsAboutTheDeclaration() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" . self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'disable' , 'name' => 'check' ] ) ) ;

        $this->assertSame( self::OTHER . "\n# " . self::CHECK . "\n" , $fake->text ) ;
        $this->assertStringContainsString( 'is declared enabled: the next sync puts it back' , $tester->getDisplay() ) ;
    }

    public function testEnableUncommentsTheLine() :void
    {
        $fake   = new FakeCrontabRunner( '# ' . self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'enable' , 'name' => 'check' ] ) ) ;

        $this->assertSame( self::CHECK . "\n" , $fake->text ) ;
        $this->assertStringNotContainsString( 'the next sync puts it back' , $tester->getDisplay() ) ;
    }

    public function testEnableAnEnabledLineWritesNothing() :void
    {
        $fake   = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'enable' , 'name' => 'check' ] ) ) ;
        $this->assertStringContainsString( 'is already enabled' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }

    public function testDisableAJobTheCrontabDoesNotHoldFails() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'disable' , 'name' => 'check' ] ) ) ;
        $this->assertStringContainsString( 'holds no line of the job "check"' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }

    public function testRemoveTakesTheLineOutAndWarnsAboutTheDeclaration() :void
    {
        $fake   = new FakeCrontabRunner( self::OTHER . "\n" . self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'remove' , 'name' => 'check' ] ) ) ;

        $this->assertSame( self::OTHER . "\n" , $fake->text ) ;
        $this->assertStringContainsString( 'still declared: the next sync adds it back' , $tester->getDisplay() ) ;
    }

    public function testRemoveALineNoJobIsDeclaredFor() :void
    {
        $fake   = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake , [] ) ;

        $this->assertSame( ExitCode::SUCCESS , $tester->execute( [ 'action' => 'remove' , 'name' => 'check' ] ) ) ;

        $this->assertSame( '' , $fake->text ) ;
        $this->assertStringNotContainsString( 'still declared' , $tester->getDisplay() ) ;
    }

    public function testRemoveNeverTouchesAnotherScope() :void
    {
        $line   = '*/5 * * * * /srv/app/check.sh # oihana-cron:otherapp:check' ;
        $fake   = new FakeCrontabRunner( $line . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'remove' , 'name' => 'check' ] ) ) ;
        $this->assertSame( $line . "\n" , $fake->text ) ;
    }

    public function testAnUnknownActionIsInvalid() :void
    {
        $tester = $this->tester( new FakeCrontabRunner() ) ;

        $this->assertSame( ExitCode::INVALID , $tester->execute( [ 'action' => 'purge' ] ) ) ;
        $this->assertStringContainsString( 'Unknown action "purge"' , $tester->getDisplay() ) ;
    }

    public function testAnActionWithoutItsNameIsInvalid() :void
    {
        $fake   = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $tester = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::INVALID , $tester->execute( [ 'action' => 'remove' ] ) ) ;
        $this->assertStringContainsString( 'needs the name of a job' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }

    public function testAWrongDeclarationFailsWhenTheCommandRuns() :void
    {
        $tester = $this->tester( new FakeCrontabRunner() , [ [ 'name' => 'check' , 'schedule' => '61 * * * *' , 'command' => '/srv/app/check.sh' ] ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'sync' ] ) ) ;
        $this->assertStringContainsString( 'The schedule "61 * * * *" of the cron job "check" is not valid' , $tester->getDisplay() ) ;
    }

    public function testAWrongScopeFailsWhenTheCommandRuns() :void
    {
        $tester = $this->tester( new FakeCrontabRunner() , init: [ CronParam::SCOPE => '' ] ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'list' ] ) ) ;
        $this->assertStringContainsString( 'The cron scope "" is not valid' , $tester->getDisplay() ) ;
    }

    public function testAnUnreadableCrontabFailsAndWritesNothing() :void
    {
        $fake              = new FakeCrontabRunner( self::CHECK . "\n" ) ;
        $fake->readFailure = [ 'crontab: permission denied' , 1 ] ;
        $tester            = $this->tester( $fake ) ;

        $this->assertSame( ExitCode::FAILURE , $tester->execute( [ 'action' => 'sync' , '--apply' => true ] ) ) ;
        $this->assertStringContainsString( 'The crontab cannot be read' , $tester->getDisplay() ) ;
        $this->assertSame( 0 , $fake->writes ) ;
    }
}
