<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use InvalidArgumentException;

use oihana\commands\cron\CronLine;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CronLine: the marker of a managed line, its scope, its name, its state.
 */
class CronLineTest extends TestCase
{
    private const string LINE = '0 6 * * * /srv/app/check.sh # oihana-cron:myapp-staging:check' ;

    public function testMarker() :void
    {
        $this->assertSame( '# oihana-cron:myapp-staging:check' , CronLine::marker( 'myapp-staging' , 'check' ) ) ;
    }

    public function testMarkerRefusesAScopeWithAColon() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'The cron scope "my:app" is not valid' ) ;

        CronLine::marker( 'my:app' , 'check' ) ;
    }

    public function testMarkerRefusesAnEmptyName() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'The cron name "" is not valid' ) ;

        CronLine::marker( 'myapp' , '' ) ;
    }

    public function testIsIdentifier() :void
    {
        $this->assertTrue  ( CronLine::isIdentifier( 'myapp-staging' ) ) ;
        $this->assertTrue  ( CronLine::isIdentifier( 'job_2' ) ) ;
        $this->assertFalse ( CronLine::isIdentifier( '-job' ) ) ;
        $this->assertFalse ( CronLine::isIdentifier( 'my job' ) ) ;
        $this->assertFalse ( CronLine::isIdentifier( 'my:job' ) ) ;
        $this->assertFalse ( CronLine::isIdentifier( '' ) ) ;
    }

    public function testNameOfALineOfTheScope() :void
    {
        $this->assertSame( 'check' , CronLine::name( self::LINE , 'myapp-staging' ) ) ;
    }

    public function testNameOfALineOfAnotherScopeIsNull() :void
    {
        $this->assertNull( CronLine::name( self::LINE , 'myapp-production' ) ) ;
        $this->assertNull( CronLine::name( self::LINE , 'myapp' ) ) ;
    }

    public function testNameOfALineWithoutMarkerIsNull() :void
    {
        $this->assertNull( CronLine::name( '0 6 * * * /srv/app/check.sh' , 'myapp-staging' ) ) ;
        $this->assertNull( CronLine::name( 'PATH=/usr/bin:/bin' , 'myapp-staging' ) ) ;
        $this->assertNull( CronLine::name( '' , 'myapp-staging' ) ) ;
    }

    public function testNameOfAMarkerInTheMiddleIsNull() :void
    {
        $this->assertNull( CronLine::name( '0 6 * * * /srv/app/check.sh # oihana-cron:myapp-staging:check --force' , 'myapp-staging' ) ) ;
    }

    public function testNameOfADisabledLine() :void
    {
        $this->assertSame( 'check' , CronLine::name( '# ' . self::LINE , 'myapp-staging' ) ) ;
    }

    public function testNameAllowsTrailingSpaces() :void
    {
        $this->assertSame( 'check' , CronLine::name( self::LINE . '  ' , 'myapp-staging' ) ) ;
    }

    public function testIsDisabled() :void
    {
        $this->assertFalse ( CronLine::isDisabled( self::LINE ) ) ;
        $this->assertTrue  ( CronLine::isDisabled( '# ' . self::LINE ) ) ;
        $this->assertTrue  ( CronLine::isDisabled( '  #' . self::LINE ) ) ;
    }

    public function testDisableThenEnableGivesTheLineBack() :void
    {
        $disabled = CronLine::disable( self::LINE ) ;

        $this->assertSame( '# ' . self::LINE , $disabled ) ;
        $this->assertSame( self::LINE , CronLine::enable( $disabled ) ) ;
    }

    public function testDisableADisabledLineChangesNothing() :void
    {
        $this->assertSame( '# ' . self::LINE , CronLine::disable( '# ' . self::LINE ) ) ;
    }

    public function testEnableAnEnabledLineChangesNothing() :void
    {
        $this->assertSame( self::LINE , CronLine::enable( self::LINE ) ) ;
    }

    public function testEnableALineCommentedByHand() :void
    {
        $this->assertSame( self::LINE , CronLine::enable( '#' . self::LINE ) ) ;
        $this->assertSame( self::LINE , CronLine::enable( '  #   ' . self::LINE ) ) ;
    }
}
