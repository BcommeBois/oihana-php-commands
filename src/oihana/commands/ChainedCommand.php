<?php

namespace oihana\commands;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use oihana\commands\options\CommandOptions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use oihana\commands\options\CommandOption;
use oihana\commands\traits\ChainedCommandsTrait;

use function oihana\commands\helpers\clearConsole;

/**
 * Symfony Console command class supporting before/after chained commands.
 *
 * This command class extends `Kernel` and integrates the `ChainedCommandsTrait`
 * to allow executing arrays of commands or callables before and after the main command logic.
 * It also integrates command options such as `--clear` to optionally clear the console
 * before execution.
 *
 * Execution flow:
 * ```
 * ┌─────────────┐
 * │   before    │ <- Array of commands/callables executed before main logic
 * └─────┬───────┘
 *       │
 *       ▼
 * ┌─────────────┐
 * │    run      │ <- Main command logic (array of commands/callables)
 * └─────┬───────┘
 *       │
 *       ▼
 * ┌─────────────┐
 * │    after    │ <- Array of commands/callables executed after main logic
 * └─────────────┘
 * ```
 *
 * Usage example:
 * ```php
 * $command = new ChainedCommand('app:chained', $container,
 * [
 *     'before' =>
 *     [
 *         fn(InputInterface $in, OutputInterface $out, $cmd) => $out->writeln('Before hook')
 *     ],
 *     'run' =>
 *     [
 *         fn(InputInterface $in, OutputInterface $out, $cmd) => $out->writeln('Main logic executed')
 *     ],
 *     'after' =>
 *     [
 *         ['name' => 'app:cleanup', 'args' => []]
 *     ]
 * ]);
 * ```
 *
 * @package oihana\commands
 * @author  Marc Alcaraz
 * @since   1.0.0
 *
 * @property ?CommandOptions $commandOptions Global command options, automatically initialized.
 */
class ChainedCommand extends Kernel
{
    /**
     * ChainedCommand constructor.
     *
     * Initializes the command with a name, dependency injection container, and optional parameters.
     * Calls `initializeChain()` from `ChainedCommandsTrait` to setup before/after/run commands.
     *
     * @param string|null $name      The command name, or null to be set in `configure()`.
     * @param Container   $container The dependency injection container.
     * @param array       $init      Initialization parameters, may include `before`, `after`, or `run` arrays.
     *
     * @throws DependencyException If the DI container fails to inject dependencies.
     * @throws NotFoundException If a dependency is missing in the container.
     * @throws ContainerExceptionInterface If the container encounters a generic error.
     * @throws NotFoundExceptionInterface If a container entry cannot be found.
     */
    public function __construct( ?string $name , Container $container , array $init = [] )
    {
        parent::__construct( $name , $container , $init ) ;
        $this->initializeChain( $init ) ;
    }

    use ChainedCommandsTrait ;

    /**
     * Configures the command options.
     *
     * Adds a `--clear` option to allow clearing the console before command execution.
     *
     * @return void
     */
    protected function configure(): void
    {
        CommandOption::configureClear( $this ) ;
    }

    /**
     * Executes the current command.
     *
     * This method handles the full lifecycle of a chained command:
     *
     * 1. Optionally clears the console if the `--clear` option is provided.
     * 2. Executes all "before" hooks defined in `$this->before`.
     * 3. Executes all "run" hooks defined in `$this->run`.
     * 4. Executes all "after" hooks defined in `$this->after`.
     * 5. Returns the final exit status.
     *
     * Execution sequence diagram:
     *
     * ```
     * ┌───────────────┐
     * │  clear console│ <- optional
     * └─────┬─────────┘
     *       │
     *       ▼
     * ┌───────────────┐
     * │    before     │ <- $this->before hooks
     * └─────┬─────────┘
     *       │
     *       ▼
     * ┌───────────────┐
     * │     run       │ <- $this->run hooks
     * └─────┬─────────┘
     *       │
     *       ▼
     * ┌───────────────┐
     * │     after     │ <- $this->after hooks
     * └───────────────┘
     * ```
     *
     * Example usage:
     * ```php
     * $status = $command->execute($input, $output);
     * // Will run all before hooks, then run hooks, then after hooks in order
     * ```
     *
     * @param InputInterface  $input  Symfony input instance.
     * @param OutputInterface $output Symfony output instance.
     *
     * @return int Exit code (0 = success, any other value = failure).
     * @throws ExceptionInterface If a command in the chain fails.
     */
    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        if( $input->hasOption( CommandOption::CLEAR ) )
        {
            clearConsole( $input->getOption( CommandOption::CLEAR ) ?? $this->commandOptions?->clear ?? false ) ;
        }

        [ $_ , $timestamp ] = $this->startCommand( $input , $output ) ;

        $status = $this->trigger( $input , $output ) ;

        return $this->endCommand( $input , $output , $status , $timestamp ) ;
    }

}