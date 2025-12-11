<?php

namespace oihana\commands\traits;

use oihana\commands\options\CommandOption;
use oihana\enums\Char;
use ReflectionClass;
use ReflectionException;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function oihana\commands\helpers\warning;
use function oihana\core\strings\key ;

/**
 * Acquire the command lock to prevent concurrent executions.
 *
 * This method ensures that the command cannot be run multiple times concurrently.
 * It uses the Symfony LockableTrait to create a process-level lock identified by
 * the given name (or by the command name when omitted).
 *
 * If the lock is already held by another running process:
 * - When `$blocking` is `true`, the call will wait until the lock is released.
 * - When `$blocking` is `false`, the call will immediately return `false` and
 *   optionally display a warning message (unless the `--force` option is used).
 *
 * The `--force` option (when available on the command) allows bypassing the lock
 * mechanism and forces the command to run even if another instance is active.
 *
 * @param InputInterface  $input    The input instance of the main command.
 * @param OutputInterface $output   The output instance of the main command.
 * @param string|null     $name     The optional unique name of the lock action.
 *                                  If omitted, the command's default name is used.
 * @param bool            $blocking Determines whether the call should wait for the
 *                                  lock to be released when it is already acquired
 *                                  by another process (`true`), or fail immediately
 *                                  (`false`).
 *
 * @return bool Returns `true` if the lock is successfully acquired or forced.
 *              Returns `false` if the lock is held by another process and the
 *              method is non-blocking.
 */
trait LockCommandTrait
{
    use LockableTrait ;

    /**
     * Acquire the command lock to prevent concurrent executions.
     *
     * @param InputInterface  $input    The input instance of the main command.
     * @param OutputInterface $output   The output instance of the main command.
     * @param string|null     $name     The optional unique name of the lock action. By default, use the command name.
     * @param bool            $blocking Determinates whether or not the call should block until the release of the lock.
     * @param string|null     $env      The optional environment of the command, ex: 'test', 'prod', ... -> add the prefix `env:name`
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    protected function acquireLock
    (
        InputInterface  $input ,
        OutputInterface $output ,
        ?string         $name     = null  ,
        bool            $blocking = false ,
        ?string         $env      = null  ,
    )
    :bool
    {
        $env   = $input->hasOption( CommandOption::ENV   ) ? $input->getOption(CommandOption::ENV   ) : $env  ;
        $force = $input->hasOption( CommandOption::FORCE ) ? $input->getOption(CommandOption::FORCE ) : false ;

        if ( empty( $name ) )
        {
            if ($this instanceof Command)
            {
                $name = $this->getName() ;
            }
            elseif ($attribute = new ReflectionClass( $this::class )->getAttributes(AsCommand::class ) )
            {
                $name = $attribute[0]->newInstance()->name ;
            }
        }

        if( !empty( $name ) && !empty( $env ) )
        {
            $name = key( $name , $env , separator: Char::COLON ) ;
        }

        if ( !$force && !$this->lock( $name , $blocking ) )
        {
            $output->writeln( warning( '⚠️ The command is already running in another process.' ) ) ;
            return false ;
        }

        return true ;
    }

    /**
     * Release the previously acquired command lock.
     *
     * This method must be called when the command has completed its execution
     * to free the lock and allow subsequent runs. If the command is not locked,
     * calling this method has no effect.
     *
     * @return void
     */
    protected function unlock() :void
    {
        $this->release() ;
    }
}