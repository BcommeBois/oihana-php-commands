<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of common Homebrew CLI commands.
 *
 * This class provides constants representing frequently used
 * Homebrew commands for package and service management on macOS
 * and Linux. It can be used to standardize command usage across
 * scripts and applications.
 *
 * Example:
 * ```php
 * use oihana\commands\enums\BrewCommands;
 *
 * // Install a package
 * shell_exec(BrewCommands::BREW_INSTALL . ' nginx');
 *
 * // List all installed packages
 * $packages = shell_exec(BrewCommands::BREW_LIST);
 * echo $packages;
 *
 * // Restart a service
 * shell_exec(BrewCommands::BREW_SERVICES_RESTART . ' nginx');
 *  ```
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class BrewCommands
{
    use ConstantsTrait ;

    /**
     * The 'brew' cli command.
     */
    public const string BREW = 'brew' ;

    /**
     * Deletes old versions and unnecessary files.
     */
    public const string BREW_CLEANUP = 'brew cleanup' ;

    /**
     * Diagnoses potential problems with your setup.
     */
    public const string BREW_DOCTOR = 'brew doctor' ;

    /**
     * Installs a package (e.g., brew install nginx).
     */
    public const string BREW_INSTALL = 'brew install' ;

    /**
     * Lists all installed packages.
     */
    public const string BREW_LIST = 'brew list' ;

    /**
     * Reinstall a package.
     */
    public const string BREW_REINSTALL = 'brew reinstall' ;

    /**
     * Searches for packages.
     */
    public const string BREW_SEARCH = 'brew search' ;

    /**
     * Cleans up unused service entries.
     */
    public const string BREW_SERVICES_CLEANUP = 'brew services cleanup' ;

    /**
     * Lists all services and their status (started/stopped).
     */
    public const string BREW_SERVICES_LIST = 'brew services list' ;

    /**
     * Restarts a service.
     */
    public const string BREW_SERVICES_RESTART = 'brew services restart' ;

    /**
     * Runs a service once (without autoloading on login).
     */
    public const string BREW_SERVICES_RUN = 'brew services run' ;

    /**
     * Starts a service and enables it to launch at login.
     */
    public const string BREW_SERVICES_START = 'brew services start' ;

    /**
     * Stops a service.
     */
    public const string BREW_SERVICES_STOP = 'brew services stop' ;

    /**
     * Uninstall a package (e.g., brew uninstall nginx).
     */
    public const string BREW_UNINSTALL = 'brew uninstall' ;

    /**
     * Updates Homebrew’s local formula index.
     */
    public const string BREW_UPDATE = 'brew update' ;

    /**
     * Upgrades all outdated packages.
     */
    public const string BREW_UPGRADE = 'brew upgrade' ;
}