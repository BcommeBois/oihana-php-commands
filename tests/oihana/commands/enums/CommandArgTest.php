<?php

declare(strict_types=1);

namespace tests\oihana\commands\enums;

use PHPUnit\Framework\TestCase;

use oihana\commands\enums\CommandArg;
use ReflectionClass;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

final class CommandArgTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(CommandArg::class . '::ACTION'), 'The ACTION constant must be defined.');
        $this->assertTrue(defined(CommandArg::class . '::INIT'), 'The INIT constant must be defined.');
    }

    public function testConstantValues(): void
    {
        $this->assertSame('action', CommandArg::ACTION, 'ACTION must equal "action".');
        $this->assertSame('init', CommandArg::INIT, 'INIT must equal "init".');
    }

    public function testAllConstants(): void
    {
        $expected =
        [
            'ACTION' => 'action',
            'INIT'   => 'init',
        ];

        $this->assertSame($expected, CommandArg::getAll(), 'getAll() must return all constants.');
    }

    public function testEnums(): void
    {
        $this->assertSame(['action', 'init'], CommandArg::enums(), 'enums() must return the sorted values.');
    }

    public function testNumberOfConstants(): void
    {
        $reflection = new ReflectionClass(CommandArg::class);

        $this->assertCount(2, $reflection->getConstants(), 'CommandArg must contain exactly 2 constants.');
    }

    public function testConfigureActionRegistersRequiredArgumentWithDefaults(): void
    {
        $command = new Command('test');

        $result = CommandArg::configureAction($command);

        $this->assertSame($command, $result, 'configureAction() must return the same command instance.');

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument(CommandArg::ACTION), 'The action argument must be registered.');

        $argument = $definition->getArgument(CommandArg::ACTION);
        $this->assertTrue($argument->isRequired(), 'The action argument must be required by default.');
        $this->assertSame('', $argument->getDescription(), 'The description must default to an empty string.');
        $this->assertNull($argument->getDefault(), 'The default value must be null by default.');
    }

    public function testConfigureActionWithCustomParameters(): void
    {
        $command = new Command('test');

        CommandArg::configureAction
        (
            $command,
            description     : 'The action to perform',
            default         : 'create',
            suggestedValues : ['create', 'update', 'delete'],
            mode            : InputArgument::OPTIONAL
        );

        $argument = $command->getDefinition()->getArgument(CommandArg::ACTION);

        $this->assertFalse($argument->isRequired(), 'The argument must be optional when mode is OPTIONAL.');
        $this->assertSame('The action to perform', $argument->getDescription(), 'The description must be set.');
        $this->assertSame('create', $argument->getDefault(), 'The default value must be set.');
    }
}
