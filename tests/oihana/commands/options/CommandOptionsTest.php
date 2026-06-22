<?php

declare(strict_types=1);

namespace tests\oihana\commands\options;

use oihana\commands\options\CommandOptions;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CommandOptions::getOptions() (sudo prefix building).
 */
class CommandOptionsTest extends TestCase
{
    public function testGetOptionsReturnsEmptyWhenSudoDisabled(): void
    {
        $options = new CommandOptions() ;
        $options->sudo = false ;

        $this->assertSame( '' , $options->getOptions() ) ;
    }

    public function testGetOptionsReturnsSudoOnlyWhenNoOwner(): void
    {
        $options = new CommandOptions() ;
        $options->sudo  = true ;
        $options->owner = null ;

        $this->assertSame( 'sudo' , $options->getOptions() ) ;
    }

    public function testGetOptionsReturnsSudoWithOwner(): void
    {
        $options = new CommandOptions() ;
        $options->sudo  = true ;
        $options->owner = 'www-data' ;

        $this->assertSame( 'sudo -u www-data' , $options->getOptions() ) ;
    }

    public function testGetOptionsTrimsOwnerAndIgnoresBlank(): void
    {
        $options = new CommandOptions() ;
        $options->sudo  = true ;
        $options->owner = '   ' ; // blank once trimmed -> no -u part

        $this->assertSame( 'sudo' , $options->getOptions() ) ;
    }

    public function testToStringDelegatesToGetOptions(): void
    {
        $options = new CommandOptions() ;
        $options->sudo  = true ;
        $options->owner = 'deploy' ;

        $this->assertSame( 'sudo -u deploy' , (string) $options ) ;
    }
}
