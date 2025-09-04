<?php

namespace oihana\commands\styles;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base helper for extending Symfony Console output styles.
 *
 * Provides a unified interface around {@see OutputInterface}, adding convenience
 * methods to access, configure, and control console output behaviors.
 *
 * Custom styles such as {@see JsonStyle} can extend this class to inherit all
 * base features while adding specialized formatting logic.
 *
 * Example:
 * ```php
 * use Symfony\Component\Console\Output\ConsoleOutput;
 * use oihana\commands\styles\JsonStyle;
 *
 * $output = new ConsoleOutput();
 * $style  = new JsonStyle($output);
 * $style->writeln('<info>Hello world!</info>');
 * ```
 * @package oihana\commands\styles
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
abstract class OutputStyle
{
    /**
     * Creates a new OutputStyle instance.
     *
     * @param OutputInterface $output The Symfony Console output implementation.
     *
     * @example
     * ```php
     * $style = new JsonStyle(new ConsoleOutput());
     * ```
     */
    public function __construct( OutputInterface $output )
    {
        $this->output = $output;
    }

    /**
     * The underlying Symfony Console output interface.
     *
     * @var OutputInterface
     */
    private OutputInterface $output ;

    /**
     * Returns the current output formatter.
     *
     * @return OutputFormatterInterface The configured output formatter.
     */
    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    /**
     * Returns the Symfony Console output reference.
     * @return OutputInterface
     */
    public function getOutput():OutputInterface
    {
        return $this->output;
    }

    /**
     * Gets the current verbosity level.
     *
     * @return int One of the `OutputInterface::VERBOSITY_*` constants.
     */
    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    /**
     * Checks whether the output is in debug mode.
     *
     * @return bool `true` if verbosity is set to debug, otherwise `false`.
     */
    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * Checks whether ANSI output decoration is enabled.
     *
     * @return bool `true` if decorated, otherwise `false`.
     */
    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    /**
     * Checks whether the output is completely silent.
     *
     * @return bool `true` if verbosity is set to silent, otherwise `false`.
     */
    public function isSilent(): bool
    {
        return $this->output->isSilent() ;
    }

    /**
     * Checks whether the verbosity level is quiet.
     *
     * @return bool `true` if verbosity is quiet, otherwise `false`.
     */
    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * Checks whether the verbosity level is verbose.
     *
     * @return bool `true` if verbosity is verbose, otherwise `false`.
     */
    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * Checks whether the verbosity level is very verbose.
     *
     * @return bool `true` if verbosity is very verbose, otherwise `false`.
     */
    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Outputs one or more blank lines to the console.
     *
     * This is a shorthand utility for adding visual separation between different parts of the output.
     * It simply writes the specified number of newline characters (`PHP_EOL`)
     * to the underlying output stream.
     *
     * @param int $count The number of new lines to output. Defaults to `1`.
     *
     * @return static
     *
     * @example
     * ```php
     * $style->newLine() ;  // Outputs 1 blank line.
     * $style->newLine(3) ; // Outputs 3 blank lines.
     * ```
     */
    public function newLine( int $count = 1 ): static
    {
        $this->output->write( str_repeat(PHP_EOL , $count ) ) ;
        return $this ;
    }

    /**
     * Enables or disables ANSI output decoration.
     *
     * @param bool $decorated `true` to enable decoration, `false` to disable it.
     *
     * @return static
     */
    public function setDecorated( bool $decorated ): static
    {
        $this->output->setDecorated( $decorated ) ;
        return $this ;
    }

    /**
     * Sets the output formatter.
     *
     * @param OutputFormatterInterface $formatter The new formatter to apply.
     *
     * @return static
     */
    public function setFormatter( OutputFormatterInterface $formatter ): static
    {
        $this->output->setFormatter( $formatter ) ;
        return $this ;
    }

    /**
     * Sets the current verbosity level.
     *
     * @param int $level One of the `OutputInterface::VERBOSITY_*` constants.
     *
     * @return static
     */
    public function setVerbosity( int $level ): static
    {
        $this->output->setVerbosity( $level ) ;
        return $this ;
    }

    /**
     * Writes a message or a set of messages to the console.
     *
     * @param string|iterable $messages The message(s) to write.
     * @param bool            $newline  Whether to append a new line after the output.
     * @param int             $type     One of the `OutputInterface::OUTPUT_*` constants.
     *
     * @return static
     *
     * @example
     * ```php
     * $style->write("Processing...", false);
     * $style->write(["Done", "Success"], true);
     * ```
     */
    public function write
    (
        string|iterable $messages ,
        bool            $newline = false ,
        int             $type    = OutputInterface::OUTPUT_NORMAL
    )
    : static
    {
        $this->output->write( $messages , $newline , $type ) ;
        return $this ;
    }

    /**
     * Writes a message or a set of messages followed by a new line.
     *
     * @param string|iterable $messages The message(s) to write.
     * @param int             $type     One of the `OutputInterface::OUTPUT_*` constants.
     *
     * @return static
     *
     * @example
     * ```php
     * $style->writeln("<info>Task completed successfully!</info>");
     * ```
     */
    public function writeln( string|iterable $messages, int $type = OutputInterface::OUTPUT_NORMAL ): static
    {
        $this->output->writeln($messages, $type);
        return $this ;
    }

    /**
     * Returns the error output stream.
     *
     * If the underlying output implements {@see ConsoleOutputInterface},
     * this method returns its dedicated error output stream. Otherwise,
     * it returns the main output instance.
     *
     * @return OutputInterface The error output interface.
     */
    protected function getErrorOutput(): OutputInterface
    {
        if ( !$this->output instanceof ConsoleOutputInterface )
        {
            return $this->output ;
        }

        return $this->output->getErrorOutput();
    }
}