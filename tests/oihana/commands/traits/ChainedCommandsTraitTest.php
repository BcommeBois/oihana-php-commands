<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\enums\CommandParam;
use oihana\commands\traits\ChainedCommandsTrait;
use PHPUnit\Framework\TestCase;

use oihana\commands\enums\ExitCode;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Dummy command class for testing the ChainedCommandsTrait.
 */
class DummyCommand extends Command
{
    use ChainedCommandsTrait;

    public ?Application $applicationMock = null;

    public function setApplicationMock(?Application $app): void
    {
        $this->applicationMock = $app;
    }

    public function getApplication(): ?Application
    {
        return $this->applicationMock;
    }
}

final class ChainedCommandsTraitTest extends TestCase
{
    private DummyCommand $command;
    private BufferedOutput $output;
    private InputInterface $input;

    protected function setUp(): void
    {
        $this->command = new DummyCommand('dummy');
        $this->output  = new BufferedOutput();
        $this->input   = $this->createStub(InputInterface::class);
    }

    /**
     * @throws ReflectionException
     */
    public function testInitializeMethods(): void
    {
        $init = [
            CommandParam::BEFORE => [['name' => 'before:cmd' , 'args' => []]],
            CommandParam::AFTER  => [['name' => 'after:cmd'  , 'args' => []]],
            CommandParam::RUN    => [['name' => 'run:cmd'    , 'args' => []]],
        ];

        $this->command->initializeBefore($init);
        $this->assertNotEmpty($this->getProperty('before'));

        $this->command->initializeAfter($init);
        $this->assertNotEmpty($this->getProperty('after'));

        $this->command->initializeRun($init);
        $this->assertNotEmpty($this->getProperty('run'));

        $this->command->initializeChain($init);
        $this->assertNotEmpty($this->getProperty('before'));
        $this->assertNotEmpty($this->getProperty('after'));
        $this->assertNotEmpty($this->getProperty('run'));
    }

    public function testBeforeAndAfterEmpty(): void
    {
        $this->assertSame(ExitCode::SUCCESS, $this->command->before($this->input, $this->output));
        $this->assertSame(ExitCode::SUCCESS, $this->command->after($this->input, $this->output));
    }

    public function testCallableExecutionSuccess(): void
    {
        $callable = function ($input, $output, $command) {
            $output->writeln("callable executed");
            return ExitCode::SUCCESS;
        };

        $this->command->initializeBefore([CommandParam::BEFORE => [$callable]]);
        $this->command->setApplicationMock(new Application()); // not used here
        $exit = $this->command->before($this->input, $this->output);

        $this->assertSame(ExitCode::SUCCESS, $exit);
        $this->assertStringContainsString("callable executed", $this->output->fetch());
    }

    public function testCallableExecutionFailureStopsChain(): void
    {
        $callable = fn() => ExitCode::FAILURE;

        $this->command->initializeBefore([CommandParam::BEFORE => [$callable]]);
        $this->command->setApplicationMock(new Application());

        $exit = $this->command->before($this->input, $this->output);
        $this->assertSame(ExitCode::FAILURE, $exit);
    }

    public function testSymfonyCommandExecution(): void
    {
        $app            = $this->createStub(Application::class);
        $symfonyCommand = $this->createStub(Command::class);

        $symfonyCommand->method('run')->willReturn(ExitCode::SUCCESS);
        $app->method('find')->willReturn($symfonyCommand);

        $this->command->setApplicationMock($app);
        $this->command->initializeBefore([
            CommandParam::BEFORE => [[CommandParam::NAME => 'test:cmd', CommandParam::ARGS => ['--opt' => 'val']]]
        ]);

        $exit = $this->command->before($this->input, $this->output);
        $this->assertSame(ExitCode::SUCCESS, $exit);
    }

    public function testSymfonyCommandExecutionFailureStopsChain(): void
    {
        $app = $this->createStub(Application::class);
        $symfonyCommand = $this->createStub(Command::class);

        $symfonyCommand->method('run')->willReturn(ExitCode::FAILURE);
        $app->method('find')->willReturn($symfonyCommand);

        $this->command->setApplicationMock($app);
        $this->command->initializeBefore([
            CommandParam::BEFORE => [[CommandParam::NAME => 'test:cmd', CommandParam::ARGS => []]]
        ]);

        $exit = $this->command->before($this->input, $this->output);
        $this->assertSame(ExitCode::FAILURE, $exit);
    }

    public function testRunCommandsWithoutApplication(): void
    {
        $this->command->initializeBefore([
            CommandParam::BEFORE => [[CommandParam::NAME => 'test:cmd', CommandParam::ARGS => []]]
        ]);

        $this->command->setApplicationMock(null);
        $exit = $this->command->before($this->input, $this->output);

        $this->assertSame(ExitCode::FAILURE, $exit);
    }

    public function testInitializeRunStoresInRunProperty(): void
    {
        $this->command->initializeRun([
            CommandParam::RUN => [['name' => 'fake:cmd', 'args' => []]],
        ]);

        $run    = $this->getProperty('run');
        $before = $this->getProperty('before');

        $this->assertNotEmpty($run, 'Expected initializeRun to populate run[]');
        $this->assertEmpty($before, 'Expected before[] to remain empty');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRunExecutesBeforeRunAndAfter(): void
    {
        $order = [];

        $before = function($i, $o, $c) use (&$order) { $order[] = 'before'; };
        $run    = function($i, $o, $c) use (&$order) { $order[] = 'run'; };
        $after  = function($i, $o, $c) use (&$order) { $order[] = 'after'; };

        $this->command->initializeChain([
            CommandParam::BEFORE => [$before],
            CommandParam::RUN    => [$run],
            CommandParam::AFTER  => [$after],
        ]);

        $this->command->setApplicationMock(new Application());
        $exitCode = $this->command->trigger($this->input, $this->output);

        $this->assertSame(ExitCode::SUCCESS, $exitCode);
        $this->assertSame(['before', 'run', 'after'], $order);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRunStopsIfBeforeFails(): void
    {
        $before = fn() => ExitCode::FAILURE;
        $run    = fn() => throw new RuntimeException("should not run");
        $after  = fn() => throw new RuntimeException("should not run");

        $this->command->initializeChain([
            CommandParam::BEFORE => [$before],
            CommandParam::RUN    => [$run],
            CommandParam::AFTER  => [$after],
        ]);

        $this->command->setApplicationMock(new Application());
        $exitCode = $this->command->trigger($this->input, $this->output);

        $this->assertSame(ExitCode::FAILURE, $exitCode);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRunStopsIfRunFails(): void
    {
        $before = fn() => ExitCode::SUCCESS;
        $run    = fn() => ExitCode::FAILURE;
        $after  = fn() => throw new RuntimeException("after should not run");

        $this->command->initializeChain([
            CommandParam::BEFORE => [$before],
            CommandParam::RUN    => [$run],
            CommandParam::AFTER  => [$after],
        ]);

        $this->command->setApplicationMock(new Application());
        $exitCode = $this->command->trigger($this->input, $this->output);

        $this->assertSame(ExitCode::FAILURE, $exitCode);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRunReturnsAfterFailureCode(): void
    {
        $before = fn() => ExitCode::SUCCESS;
        $run    = fn() => ExitCode::SUCCESS;
        $after  = fn() => ExitCode::FAILURE;

        $this->command->initializeChain([
            CommandParam::BEFORE => [$before],
            CommandParam::RUN    => [$run],
            CommandParam::AFTER  => [$after],
        ]);

        $this->command->setApplicationMock(new Application());
        $exitCode = $this->command->trigger($this->input, $this->output);

        $this->assertSame(ExitCode::FAILURE, $exitCode);
    }

    /**
     * @throws ReflectionException
     */
    private function getProperty( string $name)
    {
        $ref = new ReflectionClass($this->command);
        return $ref->getProperty($name)->getValue($this->command);
    }


}