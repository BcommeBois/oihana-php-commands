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
     * Enables or disables ANSI output decoration.
     *
     * @param bool $decorated `true` to enable decoration, `false` to disable it.
     *
     * @return void
     */
    public function setDecorated( bool $decorated ): void
    {
        $this->output->setDecorated( $decorated ) ;
    }

    /**
     * Sets the output formatter.
     *
     * @param OutputFormatterInterface $formatter The new formatter to apply.
     *
     * @return void
     */
    public function setFormatter( OutputFormatterInterface $formatter ): void
    {
        $this->output->setFormatter( $formatter ) ;
    }

    /**
     * Sets the current verbosity level.
     *
     * @param int $level One of the `OutputInterface::VERBOSITY_*` constants.
     *
     * @return void
     */
    public function setVerbosity( int $level ): void
    {
        $this->output->setVerbosity($level);
    }

    /**
     * Writes a message or a set of messages to the console.
     *
     * @param string|iterable $messages The message(s) to write.
     * @param bool            $newline  Whether to append a new line after the output.
     * @param int             $type     One of the `OutputInterface::OUTPUT_*` constants.
     *
     * @return void
     *
     * @example
     * ```php
     * $style->write("Processing...", false);
     * $style->write(["Done", "Success"], true);
     * ```
     */
    public function write(string|iterable $messages, bool $newline = false, int $type = OutputInterface::OUTPUT_NORMAL): void
    {
        $this->output->write($messages, $newline, $type);
    }

    /**
     * Writes a message or a set of messages followed by a new line.
     *
     * @param string|iterable $messages The message(s) to write.
     * @param int             $type     One of the `OutputInterface::OUTPUT_*` constants.
     *
     * @return void
     *
     * @example
     * ```php
     * $style->writeln("<info>Task completed successfully!</info>");
     * ```
     */
    public function writeln(string|iterable $messages, int $type = OutputInterface::OUTPUT_NORMAL): void
    {
        $this->output->writeln($messages, $type);
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