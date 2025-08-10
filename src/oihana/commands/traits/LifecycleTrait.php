<?php

namespace oihana\commands\traits;

use oihana\commands\enums\ExitCode;
use oihana\enums\Char;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides helper methods to manage the lifecycle of console commands.
 *
 * This trait defines utility methods for initializing and finalizing
 * Symfony Console commands with consistent output formatting.
 * It is intended to be used alongside {@see IOTrait} to handle
 * SymfonyStyle input/output operations.
 *
 * ## Responsibilities
 * - Displaying a formatted title when a command starts.
 * - Returning a start timestamp for execution time calculation.
 * - Displaying a completion message with optional execution time.
 *
 * ## Usage Example
 * ```php
 * use oihana\commands\traits\LifecycleTrait;
 * use Symfony\Component\Console\Command\Command;
 * use Symfony\Component\Console\Input\InputInterface;
 * use Symfony\Component\Console\Output\OutputInterface;
 *
 * class MyCommand extends Command
 * {
 *     use LifecycleTrait;
 *
 *     protected static $defaultName = 'app:my-command';
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         // Start the command
 *         [$io, $startTime] = $this->startCommand($input, $output);
 *
 *         // ... perform command actions ...
 *
 *         // End the command
 *         return $this->endCommand($input, $output, ExitCode::SUCCESS, $startTime);
 *     }
 * }
 * ```
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait LifecycleTrait
{
    use IOTrait ;

    /**
     * Finalizes the command execution.
     *
     * Displays a completion section in the console output with an optional
     * execution time, followed by a short farewell message. Returns the
     * provided status code.
     *
     * @param InputInterface  $input     The console input instance.
     * @param OutputInterface $output    The console output instance.
     * @param int             $status    The return status code (default: {@see ExitCode::SUCCESS}).
     * @param float           $timestamp The starting UNIX timestamp for execution time calculation (default: 0).
     *
     * @return int The status code to return from the command.
     *
     * @example
     * ```php
     * return $this->endCommand($input, $output, ExitCode::SUCCESS, $startTime);
     * ```
     */
    public function endCommand
    (
        InputInterface  $input,
        OutputInterface $output ,
        int             $status    = ExitCode::SUCCESS ,
        float           $timestamp = 0
    )
    : int
    {
        $io = $this->getIO( $input , $output ) ;

        $message = $timestamp > 0
            ?  sprintf("✅  Done in %s" , Helper::formatTime( microtime(true ) - $timestamp ) )
            : "Done !" ;

        $io->section( $message ) ;
        $io->text( "Thank you and see you soon!" ) ;
        $io->newLine() ;

        return $status ;
    }

    /**
     * Initializes the command execution.
     *
     * Displays a formatted title in the console containing the command name
     * and an optional action property, then returns the I/O helper instance
     * and the current UNIX timestamp.
     *
     * @param InputInterface  $input  The console input instance.
     * @param OutputInterface $output The console output instance.
     *
     * @return array{0: \Symfony\Component\Console\Style\SymfonyStyle, 1: float}
     *               An array containing the SymfonyStyle I/O instance and the current timestamp.
     *
     * @example
     * ```php
     * [$io, $startTime] = $this->startCommand($input, $output);
     * ```
     */
    protected function startCommand( InputInterface $input, OutputInterface $output ): array
    {
        $timestamp = microtime(true ) ;

        $io = $this->getIO( $input , $output ) ;

        $title = [ $this->getName() ] ;

        if( isset( $this->action ) && $this->action != Char::EMPTY )
        {
            $title[] = $this->action ;
        }

        $title = implode( Char::SPACE , $title ) ;

        $io->title( ucfirst( $title ) ) ;

        return [ $io , $timestamp ] ;
    }
}