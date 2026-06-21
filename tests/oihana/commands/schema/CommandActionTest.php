<?php

declare(strict_types=1);

namespace tests\oihana\commands\schema;

use PHPUnit\Framework\TestCase;

use oihana\commands\schema\CommandAction;

final class CommandActionTest extends TestCase
{
    public function testContextConstant(): void
    {
        $this->assertSame('https://schema.oihana.xyz', CommandAction::CONTEXT, 'The CONTEXT constant must be defined.');
    }

    public function testConstructorSetsDefaultProperties(): void
    {
        $action = new CommandAction();

        $this->assertSame(get_current_user(), $action->agent, 'The agent must default to the current system user.');
        $this->assertSame(gethostname(), $action->location, 'The location must default to the hostname.');
        $this->assertNotNull($action->identifier, 'The identifier must be generated.');
        $this->assertNotEmpty((string) $action->identifier, 'The identifier must not be empty.');
        $this->assertNotNull($action->startTime, 'The startTime must be set.');
    }

    public function testIdentifierIsUniquePerInstance(): void
    {
        $first  = new CommandAction();
        $second = new CommandAction();

        $this->assertNotSame((string) $first->identifier, (string) $second->identifier, 'Each instance must get a unique identifier.');
    }

    public function testConstructorHydratesFromArray(): void
    {
        $action = new CommandAction(['name' => 'do-something']);

        $this->assertSame('do-something', $action->name, 'The init array must hydrate the parent properties.');
    }
}
