<?php

namespace oihana\commands\traits;

use oihana\commands\options\CommandOption;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides support for decryption options in console commands.
 *
 * This trait defines a flag and helper methods to determine whether
 * command execution should perform decryption, based on default values
 * or runtime input options.
 *
 * @package oihana\commands\traits
 * author  Marc Alcaraz
 * @since   1.0.0
 */
trait DecryptTrait
{
    use PassphraseTrait ;

    /**
     * Indicates whether decryption should be applied by default.
     *
     * This value can be overridden at runtime via the console option {@see CommandOption::DECRYPT}.
     *
     * @var bool
     */
    public bool $decrypt = true ;

    /**
     * Initializes the internal decryption flag.
     *
     * Typically called during command setup with an array of options.
     *
     * @param array $init An associative array of initial options, possibly containing {@see CommandOption::DECRYPT}.
     * @return static Returns the current instance for method chaining.
     */
    public function initializeDecrypt( array $init = [] ):static
    {
        $this->decrypt = $init[ CommandOption::DECRYPT ] ?? $this->decrypt ;
        return $this ;
    }

    /**
     * Determines whether decryption should be applied for the given input.
     *
     * This checks both the runtime console option {@see CommandOption::DECRYPT}
     * and the default {@see $decrypt} flag.
     *
     * @param InputInterface $input The console input instance.
     * @return bool True if decryption should be performed, false otherwise.
     */
    public function shouldDecrypt( InputInterface $input ):bool
    {
        return $input->getOption( CommandOption::DECRYPT ) || $this->decrypt ;
    }
}