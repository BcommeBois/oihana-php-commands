<?php

namespace oihana\commands\traits;

use Stringable;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Traits to initialize and use the PSR-3 compliant console logger in the commands.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ConsoleLoggerTrait
{
    /**
     * The console logger reference.
     * @var ?ConsoleLogger
     */
    public ?ConsoleLogger $console ;

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public function alert( string|Stringable $message, array $context = []): void
    {
        $this->console?->alert( $message , $context );
    }

    /**
     * Critical conditions.
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical( string|Stringable $message, array $context = []):void
    {
        $this->console?->critical( $message , $context );
    }

    /**
     * Detailed debug information.
     */
    public function debug( string|Stringable $message, array $context = []):void
    {
        $this->console?->critical( $message , $context );
    }

    /**
     * System is unusable.
     */
    public function emergency( string|Stringable $message, array $context = [] ): void
    {
        $this->console?->emergency( $message , $context );
    }

    /**
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     */
    public function error( string|Stringable $message, array $context = []):void
    {
        $this->console?->error( $message , $context );
    }

    /**
     * Initialize the internal console logger.
     * @param OutputInterface|null $output
     * @param array $verbosityLevelMap
     * @param array $formatLevelMap
     * @return void
     */
    public function initializeConsoleLogger( ?OutputInterface $output = null , array $verbosityLevelMap = [] , array $formatLevelMap = []  ):void
    {
        $this->console = isset( $output ) ? new ConsoleLogger( $output , $verbosityLevelMap , $formatLevelMap ) : null ;
    }

    /**
     * Normal but significant events.
     */
    public function notice( string|Stringable $message, array $context = []):void
    {
        $this->console?->notice( $message , $context );
    }

    /**
     * Interesting events.
     * Example: User logs in, SQL logs.
     */
    public function info( string|Stringable $message, array $context = []):void
    {
        $this->console?->info( $message , $context );
    }

    /**
     * Logs with an arbitrary level.
     * @param mixed $level
     * @param string|Stringable $message
     * @param array $context
     */
    public function log( mixed $level, string|Stringable $message, array $context = []):void
    {
        $this->console?->log( $level , $message , $context );
    }

    /**
     * Exceptional occurrences that are not errors.
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning( string|Stringable $message, array $context = []):void
    {
        $this->console?->warning( $message , $context );
    }
}