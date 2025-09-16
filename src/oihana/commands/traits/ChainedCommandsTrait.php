<?php

namespace oihana\commands\traits;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use oihana\commands\enums\CommandParam;
use oihana\commands\enums\ExitCode;

/**
 * Provides before/after command chaining capabilities for Symfony Console commands.
 *
 * This trait allows a command to define arrays of commands or callables to be executed
 * before (`$before`) and after (`$after`) the main logic of the command.
 * It supports both Symfony commands (by name + arguments) and PHP callables.
 *
 * Callables must have the following signature:
 * ```php
 * function(InputInterface $input, OutputInterface $output, object $command): int|null
 * ```
 * They can optionally return an integer exit code (0 = success, any other value = failure).
 *
 * ## Usage Example
 * ```php
 * class MyCommand extends Command
 * {
 *     use ChainedCommandsTrait;
 *
 *     protected array $before =
 *     [
 *        [ 'name' => 'app:check-db', 'args' => [] ],
 *        fn(InputInterface $input, OutputInterface $output, $command) => $output->writeln('Before hook executed')
 *     ];
 *
 *     protected array $after = [
 *     [
 *        'name' => 'app:clear-cache', 'args' => ['--env' => 'prod'] ],
 *        fn(InputInterface $input, OutputInterface $output, $command) => $output->writeln('After hook executed')
 *     ];
 * }
 * ```
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 *
 * @property array<int,mixed> $before Commands or callables executed before the main command.
 * @property array<int,mixed> $after Commands or callables executed after the main command.
 */
trait ChainedCommandsTrait
{
    /**
     * @var array<int,array{name:string,args:array}> Commands to run after the main command.
     */
    protected array $after = [] ;

    /**
     * @var array<int,array{name:string,args:array}> Commands to run before the main command.
     */
    protected array $before = [] ;

    /**
     * Executes all after-commands.
     */
    public function after( InputInterface $input , OutputInterface $output ) :int
    {
        if ( empty( $this->after ) )
        {
            return ExitCode::SUCCESS;
        }
        return $this->runCommands( $this->after , $input , $output ) ;
    }

    /**
     * Executes all before-commands.
     */
    public function before( InputInterface $input , OutputInterface $output ) :int
    {
        if ( empty( $this->before ) )
        {
            return ExitCode::SUCCESS;
        }
        return $this->runCommands($this->before, $input , $output ) ;
    }

    /**
     * Initialize the list of commands to run after the main command.
     * @param array $init
     * @return $this
     */
    public function initializeAfter( array $init = [] ):static
    {
        $this->after = $init[ CommandParam::AFTER ] ?? [] ;
        return $this ;
    }

    /**
     * Initialize the list of commands to run before the main command.
     * @param array $init
     * @return $this
     */
    public function initializeBefore( array $init = [] ):static
    {
        $this->before = $init[ CommandParam::BEFORE ] ?? [] ;
        return $this ;
    }

    /**
     * Initialize the list of commands to run after and before the main command.
     * @param array $init
     * @return $this
     */
    public function initializeChain( array $init = [] ):static
    {
        $this->after = $init[ CommandParam::AFTER ] ?? [] ;
        $this->before = $init[ CommandParam::BEFORE ] ?? [] ;
        return $this ;
    }

    /**
     * Runs an array of chained commands or callables.
     *
     * This method iterates over a list of commands or callables and executes each in order.
     * It supports two types of entries in the `$commands` array:
     *
     * 1. **Symfony command arrays**:
     *    ```php
     *    [
     *        'name' => 'app:my-command', // The name of the Symfony command
     *        'args' => ['--option' => 'value'] // Optional array of arguments/options
     *    ]
     *    ```
     *
     * 2. **PHP callables**:
     *    ```php
     *    fn(InputInterface $input, OutputInterface $output, object $command): int|null => { ... }
     *    ```
     *    - The callable receives the current `$input`, `$output`, and the command instance (`$this`).
     *    - It can optionally return an integer exit code (0 = success, any other value = failure).
     *
     * If any command or callable returns a non-success exit code, the chain is immediately stopped
     * and that code is returned.
     *
     * @param array<int,array{name:string,args:array}|callable> $commands
     *        List of commands or callables to execute.
     * @param InputInterface $input The input instance of the main command.
     * @param OutputInterface $output The output instance of the main command.
     *
     * @return int Returns `ExitCode::SUCCESS` if all commands executed successfully,
     *             otherwise returns the first non-success exit code encountered.
     *
     * @throws CommandNotFoundException If a named Symfony command cannot be found in the application.
     */
    protected function runCommands( array $commands , InputInterface $input , OutputInterface $output ): int
    {
        if ( empty( $commands ) )
        {
            return ExitCode::SUCCESS;
        }

        $app = $this->getApplication();

        if ( !$app )
        {
            return ExitCode::FAILURE;
        }

        foreach ( $commands as $cmd )
        {
            if ( is_callable( $cmd ) )
            {
                $result = $cmd( $input , $output , $this ) ;
                if ( is_int( $result ) && $result !== ExitCode::SUCCESS )
                {
                    return $result;
                }
                continue;
            }

            $name = $cmd[ CommandParam::NAME ] ?? null ;
            $args = $cmd[ CommandParam::ARGS ] ?? [] ;

            if ( !isset( $name ) )
            {
                continue ;
            }

            $command = $app->find( $name );
            $input = new ArrayInput( array_merge( [ CommandParam::COMMAND => $name ] , $args ) );

            $exitCode = $command->run( $input , $output ) ;

            if ( $exitCode !== ExitCode::SUCCESS )
            {
                return $exitCode ; // stop chain if error
            }
        }

        return ExitCode::SUCCESS;
    }

}