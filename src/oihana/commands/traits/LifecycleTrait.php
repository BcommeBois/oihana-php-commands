<?php

namespace oihana\commands\traits;

use DateTimeImmutable;
use oihana\commands\enums\ExitCode;
use oihana\enums\Char;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides lifecycle helper methods for Symfony Console commands.
 *
 * This trait standardizes the initialization and finalization workflow of
 * console commands by offering a consistent way to:
 *
 * - Render a formatted title when a command starts.
 * - Capture both the UNIX timestamp and the DateTime reference used for
 *   execution time calculation.
 * - Display a completion summary when the command ends, including optional
 *   start/end dates and human-readable duration.
 *
 * It is designed to be used alongside {@see IOTrait}, which provides the
 * `getIO()` method returning a configured {@see SymfonyStyle} instance.
 *
 * ## Responsibilities
 * - Preparing a SymfonyStyle instance consistently across commands.
 * - Automatically handling start/end timestamps.
 * - Rendering a user-friendly end-of-execution summary.
 *
 * ## Usage Example
 * ```php
 * use oihana\commands\traits\LifecycleTrait;
 * use oihana\commands\enums\ExitCode;
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
 *         // Start
 *         [$io, $startTime, $startDate] = $this->startCommand($input, $output);
 *
 *         // ... command logic ...
 *
 *         // End
 *         return $this->endCommand(
 *             $input,
 *             $output,
 *             ExitCode::SUCCESS,
 *             $startTime,
 *             $startDate
 *         );
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
     * Finalizes the execution of the command.
     *
     * This method displays an end timestamp (if a start date was provided),
     * prints a completion message, and optionally shows the total execution
     * time in a human-readable format. It returns the given exit code so it
     * can be directly used as the return value of `execute()`.
     *
     * @param InputInterface      $input      The console input instance.
     * @param OutputInterface     $output     The console output instance.
     * @param int                 $status     The status code to return
     *                                         (defaults to {@see ExitCode::SUCCESS}).
     * @param float               $timestamp  The UNIX timestamp recorded at the
     *                                         start of the command (microtime true).
     * @param ?DateTimeImmutable  $startDate  The optional DateTime reference
     *                                         used to display the start/end date.
     * @param string              $format     Date formatting string used when
     *                                         rendering start/end times (default: `Y-m-d H:i:s`).
     *
     * @return int The exit code to return from the command.
     *
     * @example
     * ```php
     * return $this->endCommand(
     *     $input,
     *     $output,
     *     ExitCode::SUCCESS,
     *     $startTime,
     *     $startDate
     * );
     * ```
     */
    public function endCommand
    (
        InputInterface     $input  ,
        OutputInterface    $output ,
        int                $status    = ExitCode::SUCCESS ,
        float              $timestamp = 0 ,
        ?DateTimeImmutable $startDate = null ,
        string             $format    = 'Y-m-d H:i:s'
    )
    : int
    {
        $io = $this->getIO( $input , $output ) ;

        if ( $startDate )
        {
            $end = new DateTimeImmutable();
            $io->writeln( sprintf('🕒 End: <info>%s</info>', $end->format( $format ) ) ) ;
        }

        $duration = $timestamp > 0 ? microtime(true) - $timestamp : null ;
        if ( $duration !== null )
        {
            $io->section( sprintf("✅  Done in %s", Helper::formatTime( $duration ) ) ) ;
        }
        else
        {
            $io->section("✅  Done!");
        }

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
     * @return array{0: SymfonyStyle, 1: float}
     *               An array containing the SymfonyStyle I/O instance and the current timestamp.
     *
     * @example
     * ```php
     * [$io, $startTime] = $this->startCommand($input, $output);
     * ```
     */
    protected function startCommand( InputInterface $input , OutputInterface $output ): array
    {
        $timestamp = microtime(true ) ;
        $startDate = new DateTimeImmutable();

        $io = $this->getIO( $input , $output ) ;

        $title = [ $this->getName() ] ;

        if( isset( $this->action ) && $this->action != Char::EMPTY )
        {
            $title[] = $this->action ;
        }

        $title = implode( Char::SPACE , $title ) ;

        $io->title( ucfirst( $title ) ) ;

        return [ $io , $timestamp , $startDate ] ;
    }
}