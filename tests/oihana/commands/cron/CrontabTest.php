<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use RuntimeException;

use oihana\commands\cron\Crontab;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Crontab: reading, writing and copying a crontab, through a fake `crontab` program.
 */
class CrontabTest extends TestCase
{
    private string $directory ;

    protected function setUp() :void
    {
        $this->directory = sys_get_temp_dir() . '/oihana-crontab-test-' . bin2hex( random_bytes( 4 ) ) ;
    }

    protected function tearDown() :void
    {
        foreach( glob( $this->directory . '/{,.}*' , GLOB_BRACE ) ?: [] as $file )
        {
            if( is_file( $file ) )
            {
                unlink( $file ) ;
            }
        }

        if( is_dir( $this->directory ) )
        {
            rmdir( $this->directory ) ;
        }
    }

    public function testReadGivesTheLines() :void
    {
        $fake = new FakeCrontabRunner( "PATH=/usr/bin:/bin\n\n0 6 * * * /srv/app/check.sh\n" ) ;

        $this->assertSame( [ 'PATH=/usr/bin:/bin' , '' , '0 6 * * * /srv/app/check.sh' ] , $fake->crontab()->read() ) ;
        $this->assertSame( [ [ 'crontab' , '-l' ] ] , $fake->calls ) ;
    }

    public function testReadKeepsTheLastLineWithoutLineEnd() :void
    {
        $fake = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh" ) ;

        $this->assertSame( [ '0 6 * * * /srv/app/check.sh' ] , $fake->crontab()->read() ) ;
    }

    public function testReadKeepsTrailingEmptyLines() :void
    {
        $fake = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n\n" ) ;

        $this->assertSame( [ '0 6 * * * /srv/app/check.sh' , '' ] , $fake->crontab()->read() ) ;
    }

    public function testReadAnEmptyCrontab() :void
    {
        $this->assertSame( [] , new FakeCrontabRunner( '' )->crontab()->read() ) ;
    }

    public function testReadWithoutCrontabGivesNoLine() :void
    {
        $this->assertSame( [] , new FakeCrontabRunner()->crontab()->read() ) ;
    }

    public function testAFailedReadRaises() :void
    {
        $fake              = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n" ) ;
        $fake->readFailure = [ "crontab: cannot open your crontab\n" , 1 ] ;

        $this->expectException( RuntimeException::class ) ;
        $this->expectExceptionMessage( 'The crontab cannot be read (status 1): crontab: cannot open your crontab' ) ;

        $fake->crontab()->read() ;
    }

    public function testWriteGivesTheLinesEndedByALineEnd() :void
    {
        $fake = new FakeCrontabRunner() ;

        $fake->crontab()->write( [ 'PATH=/usr/bin' , '' , '0 6 * * * /srv/app/check.sh' ] ) ;

        $this->assertSame( "PATH=/usr/bin\n\n0 6 * * * /srv/app/check.sh\n" , $fake->text ) ;
        $this->assertSame( 'crontab' , $fake->calls[ 0 ][ 0 ] ) ;
    }

    public function testWriteNoLineGivesAnEmptyCrontab() :void
    {
        $fake = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n" ) ;

        $fake->crontab()->write( [] ) ;

        $this->assertSame( '' , $fake->text ) ;
    }

    public function testWriteRemovesItsTemporaryFile() :void
    {
        $fake = new FakeCrontabRunner() ;

        $fake->crontab()->write( [ '0 6 * * * /srv/app/check.sh' ] ) ;

        $this->assertCount( 1 , $fake->writtenFiles ) ;
        $this->assertFileDoesNotExist( $fake->writtenFiles[ 0 ] ) ;
    }

    public function testAFailedWriteRaisesAndRemovesItsTemporaryFile() :void
    {
        $fake               = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n" ) ;
        $fake->writeFailure = [ "\"/tmp/crontab-x\":1: bad minute\nerrors in crontab file, can't install.\n" , 1 ] ;

        try
        {
            $fake->crontab()->write( [ '61 6 * * * /srv/app/check.sh' ] ) ;
            $this->fail( 'A refused crontab must raise.' ) ;
        }
        catch( RuntimeException $exception )
        {
            $this->assertStringContainsString( 'The crontab cannot be written (status 1)' , $exception->getMessage() ) ;
            $this->assertStringContainsString( 'bad minute' , $exception->getMessage() ) ;
        }

        $this->assertFileDoesNotExist( $fake->writtenFiles[ 0 ] ) ;
        $this->assertSame( "0 6 * * * /srv/app/check.sh\n" , $fake->text ) ;
    }

    public function testWriteRaisesWhenNoTemporaryFileCanBeCreated() :void
    {
        $fake    = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n" ) ;
        $crontab = new class( $fake->runner() ) extends Crontab
        {
            protected function temporaryFile() :string|false
            {
                return false ;
            }
        } ;

        try
        {
            $crontab->write( [ 'new' ] ) ;
            $this->fail( 'A crontab without temporary file must raise.' ) ;
        }
        catch( RuntimeException $exception )
        {
            $this->assertSame( 'The temporary file of the crontab cannot be created.' , $exception->getMessage() ) ;
        }

        $this->assertSame( [] , $fake->calls ) ;
    }

    public function testWriteRaisesWhenTheTemporaryFileCannotBeWritten() :void
    {
        mkdir( $this->directory , 0700 ) ;

        $fake    = new FakeCrontabRunner( "0 6 * * * /srv/app/check.sh\n" ) ;
        $crontab = new class( $fake->runner() , $this->directory ) extends Crontab
        {
            public function __construct( \Closure $runner , private readonly string $path )
            {
                parent::__construct( $runner ) ;
            }

            protected function temporaryFile() :string|false
            {
                return $this->path ; // a directory: nothing can be written to it
            }
        } ;

        try
        {
            $crontab->write( [ 'new' ] ) ;
            $this->fail( 'An unwritable temporary file must raise.' ) ;
        }
        catch( RuntimeException $exception )
        {
            $this->assertStringContainsString( 'of the crontab cannot be written' , $exception->getMessage() ) ;
        }

        $this->assertSame( [] , $fake->calls ) ;
        $this->assertSame( "0 6 * * * /srv/app/check.sh\n" , $fake->text ) ;
    }

    public function testBackupCreatesTheDirectoryAndACopyOnlyTheUserReads() :void
    {
        $path = new FakeCrontabRunner()->crontab()->backup( [ 'PATH=/usr/bin' , '0 6 * * * /srv/app/check.sh' ] , $this->directory ) ;

        $this->assertDirectoryExists( $this->directory ) ;
        $this->assertMatchesRegularExpression( '#/crontab-\d{8}-\d{6}-\d{6}\.bak$#' , $path ) ;
        $this->assertSame( "PATH=/usr/bin\n0 6 * * * /srv/app/check.sh\n" , file_get_contents( $path ) ) ;
        $this->assertSame( 0600 , fileperms( $path ) & 0777 ) ;
    }

    public function testBackupKeepsOnlyTheMostRecentCopies() :void
    {
        mkdir( $this->directory , 0700 ) ;

        foreach( [ '20200101-000000-000001' , '20200101-000000-000002' , '20200101-000000-000003' ] as $stamp )
        {
            file_put_contents( $this->directory . '/crontab-' . $stamp . '.bak' , 'old' ) ;
        }

        file_put_contents( $this->directory . '/notes.txt' , 'not a copy' ) ;

        $path = new FakeCrontabRunner()->crontab()->backup( [ 'new' ] , $this->directory , 2 ) ;

        $copies = glob( $this->directory . '/crontab-*.bak' ) ?: [] ;
        sort( $copies ) ;

        $this->assertSame( [ $this->directory . '/crontab-20200101-000000-000003.bak' , $path ] , $copies ) ;
        $this->assertFileExists( $this->directory . '/notes.txt' ) ;
    }

    public function testBackupKeepsAtLeastTheNewCopy() :void
    {
        $path = new FakeCrontabRunner()->crontab()->backup( [ 'new' ] , $this->directory , 0 ) ;

        $this->assertSame( [ $path ] , glob( $this->directory . '/crontab-*.bak' ) ) ;
    }

    public function testBackupRaisesWhenTheCopyCannotBeWritten() :void
    {
        mkdir( $this->directory , 0500 ) ;

        try
        {
            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'cannot be written' ) ;

            new FakeCrontabRunner()->crontab()->backup( [ 'new' ] , $this->directory ) ;
        }
        finally
        {
            chmod( $this->directory , 0700 ) ;
        }
    }

    public function testBackupRaisesWhenTheDirectoryCannotBeCreated() :void
    {
        touch( $this->directory ) ;

        try
        {
            $this->expectException( RuntimeException::class ) ;
            $this->expectExceptionMessage( 'cannot be created' ) ;

            new FakeCrontabRunner()->crontab()->backup( [ 'new' ] , $this->directory . '/sub' ) ;
        }
        finally
        {
            unlink( $this->directory ) ;
        }
    }

}
