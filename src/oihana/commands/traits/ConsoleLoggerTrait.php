<?php

namespace oihana\commands\traits;

use oihana\logging\CompositeLogger;
use oihana\logging\LoggerTrait;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides convenient integration of a PSR-3 compliant console logger
 * (Symfony ConsoleLogger) into any command or service using {@see LoggerTrait}.
 *
 * This trait transparently upgrades the internal logger to a
 * {@see CompositeLogger} when needed, allowing you to register multiple loggers
 * at once (file loggers, console logger, debug logger, etc.).
 *
 * Key features:
 * - Automatic management of a {@see CompositeLogger} wrapper.
 * - Seamless creation and replacement of the Symfony {@see ConsoleLogger}.
 * - Ability to dynamically add, remove, or clear loggers at runtime.
 * - Full interoperability with PSR-3 loggers already initialized via {@see LoggerTrait}.
 *
 * Typical usage:
 * ```php
 * class MyCommand extends Command
 * {
 *     use ConsoleLoggerTrait;
 *
 *     protected function execute(InputInterface $input, OutputInterface $output)
 *     {
 *         // Initialize console logger (verbosity mapping optional)
 *         $this->initializeConsoleLogger($output);
 *
 *         // Log through all registered loggers
 *         $this->info("Starting command");
 *
 *         // Add a file logger dynamically
 *         $this->addLogger(new FileLogger('/var/log/app.log'));
 *
 *         return Command::SUCCESS;
 *     }
 * }
 * ```
 *
 * Notes:
 * - When a console logger is initialized, it is automatically added to the composite.
 * - When replaced or cleared, the previous console logger is properly removed.
 * - If a logger is already set via {@see LoggerTrait::initializeLogger()}, it is
 *   preserved and wrapped into a composite transparently.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ConsoleLoggerTrait
{
    use LoggerTrait ;

    /**
     * The console logger reference.
     * @var ?ConsoleLogger
     */
    public ?ConsoleLogger $console ;

    /**
     * Adds a logger to the composite.
     *
     * @param LoggerInterface $logger The logger instance to add
     *
     * @return static Returns the current instance for method chaining
     */
    public function addLogger( LoggerInterface $logger ) :static
    {
        $this->compositeLogger()->addLogger( $logger ) ;
        return $this ;
    }

    /**
     * Removes all registered loggers.
     *
     * @return static Returns the current instance for method chaining
     */
    public function clearLogger(): static
    {
        $this->compositeLogger()->clear();
        $this->console = null;
        return $this;
    }

    /**
     * Checks if a logger is registered in the composite.
     *
     * @param LoggerInterface $logger The logger instance to check
     *
     * @return bool True if the logger is registered, false otherwise
     */
    public function hasLogger( LoggerInterface $logger ): bool
    {
        return $this->logger instanceof CompositeLogger && $this->logger->hasLogger( $logger ) ;
    }

    /**
     * Initialize the internal console logger.
     * @param OutputInterface|null $output
     * @param array $verbosityLevelMap
     * @param array $formatLevelMap
     * @return static
     */
    public function initializeConsoleLogger
    (
        ?OutputInterface $output            = null ,
        array            $verbosityLevelMap = [] ,
        array            $formatLevelMap    = []
    )
    :static
    {
        if( $this->logger instanceof CompositeLogger && isset( $this->console ) )
        {
            $this->logger->removeLogger( $this->console ) ;
        }

        $this->console = isset( $output ) ? new ConsoleLogger( $output , $verbosityLevelMap , $formatLevelMap ) : null ;

        if ( $this->console )
        {
            $this->compositeLogger()->addLogger( $this->console ) ;
        }

        return $this ;
    }

    /**
     * Removes a logger from the composite by reference.
     *
     * @param LoggerInterface $logger The logger instance to remove
     *
     * @return static Returns the current instance for method chaining
     */
    public function removeLogger( LoggerInterface $logger ): static
    {
        if( $this->logger instanceof CompositeLogger )
        {
            $this->logger->removeLogger( $logger ) ;
        }

        if( $logger === $this->console )
        {
            $this->console = null ;
        }

        return $this ;
    }

    /**
     * Ensures $this->logger is a CompositeLogger and returns it.
     */
    protected function compositeLogger() :CompositeLogger
    {
        if ( !$this->logger instanceof CompositeLogger )
        {
            $existing     = $this->logger ;
            $this->logger = new CompositeLogger() ;

            if ( $existing instanceof LoggerInterface )
            {
                $this->logger->addLogger( $existing ) ;
            }
        }
        return $this->logger ;
    }
}