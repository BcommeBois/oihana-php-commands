<?php

namespace oihana\commands\traits;

use oihana\commands\options\CommandOption;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides support for encryption options in console commands.
 *
 * This trait defines a flag and helper methods to determine whether
 * command execution should perform encryption, based on default values
 * or runtime input options.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait EncryptTrait
{
    use PassphraseTrait ;

    /**
     * Indicates whether encryption should be applied by default.
     *
     * This value can be overridden at runtime via the console option {@see CommandOption::ENCRYPT}.
     *
     * @var bool
     */
    public bool $encrypt = true ;

    /**
     * Initializes the internal encryption flag.
     *
     * Typically called during command setup with an array of options.
     *
     * @param array $init An associative array of initial options, possibly containing {@see CommandOption::ENCRYPT}.
     * @return static Returns the current instance for method chaining.
     */
    public function initializeEncrypt( array $init = [] ):static
    {
        $this->encrypt = $init[ CommandOption::ENCRYPT ] ?? $this->encrypt ;
        return $this ;
    }

    /**
     * Determines whether encryption should be applied for the given input.
     *
     * This checks both the runtime console option {@see CommandOption::ENCRYPT}
     * and the default {@see $encrypt} flag.
     *
     * @param InputInterface $input The console input instance.
     * @return bool True if encryption should be performed, false otherwise.
     */
    public function shouldEncrypt( InputInterface $input ):bool
    {
        return $input->getOption( CommandOption::ENCRYPT ) || $this->encrypt ;
    }
}