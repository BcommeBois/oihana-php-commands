<?php

declare(strict_types=1);

namespace tests\oihana\commands\options;

use oihana\commands\options\CommandOption;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Command\Command;

/**
 * Unit tests for CommandOption::configure*() helpers.
 */
class CommandOptionTest extends TestCase
{
    private function command(): Command
    {
        return new Command( 'test:cmd' ) ;
    }

    public function testConfigureClearAddsOption(): void
    {
        $command = CommandOption::configureClear( $this->command() ) ;

        $this->assertTrue( $command->getDefinition()->hasOption( CommandOption::CLEAR ) ) ;
    }

    public function testConfigureClearSkipsWhenDisabled(): void
    {
        $command = CommandOption::configureClear( $this->command() , false ) ;

        $this->assertFalse( $command->getDefinition()->hasOption( CommandOption::CLEAR ) ) ;
    }

    public function testConfigureEnvAddsOption(): void
    {
        $command = CommandOption::configureEnv( $this->command() ) ;

        $this->assertTrue( $command->getDefinition()->hasOption( CommandOption::ENV ) ) ;
    }

    public function testConfigureEnvSkipsWhenDisabled(): void
    {
        $command = CommandOption::configureEnv( $this->command() , false ) ;

        $this->assertFalse( $command->getDefinition()->hasOption( CommandOption::ENV ) ) ;
    }

    public function testConfigureForceAddsOptionWithShortcut(): void
    {
        $command = CommandOption::configureForce( $this->command() , true , CommandOption::FORCE_SHORTCUT ) ;

        $definition = $command->getDefinition() ;
        $this->assertTrue( $definition->hasOption( CommandOption::FORCE ) ) ;
        $this->assertTrue( $definition->hasShortcut( CommandOption::FORCE_SHORTCUT ) ) ;
    }

    public function testConfigureForceSkipsWhenDisabled(): void
    {
        $command = CommandOption::configureForce( $this->command() , false ) ;

        $this->assertFalse( $command->getDefinition()->hasOption( CommandOption::FORCE ) ) ;
    }
}
