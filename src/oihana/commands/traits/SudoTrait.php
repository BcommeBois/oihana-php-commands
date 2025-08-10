<?php

namespace oihana\commands\traits;

use RuntimeException;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\CommandOptions;

/**
 * Trait to handle sudo authentication and session management for commands.
 *
 * Provides methods to:
 * - Authenticate with sudo once to cache credentials.
 * - Optionally keep the sudo session alive by refreshing credentials in the background.
 * - Stop the background keep-alive process.
 *
 * This trait is useful for commands that require elevated privileges and want
 * to avoid repeatedly prompting for the sudo password.
 *
 * ## Usage Example
 * ```php
 * use oihana\commands\traits\SudoTrait;
 * use Symfony\Component\Console\Command\Command;
 * use Symfony\Component\Console\Input\InputInterface;
 * use Symfony\Component\Console\Output\OutputInterface;
 *
 * class MySudoCommand extends Command
 * {
 *     use SudoTrait;
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         try {
 *             $this->sudoAuthenticate(null, true, false, true);
 *             // Run privileged operations here...
 *         } catch (\RuntimeException $e) {
 *             $output->writeln('<error>' . $e->getMessage() . '</error>');
 *             return 1;
 *         }
 *
 *         // Optionally stop the keep-alive process later
 *         $this->sudoStopKeepAlive(true);
 *
 *         return 0;
 *     }
 * }
 * ```
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait SudoTrait
{
    use CommandTrait ;

    /**
     * Attempts to authenticate with sudo once to cache credentials for subsequent commands.
     *
     * Optionally keeps the sudo session alive by refreshing the authentication token
     * in the background at regular intervals (non-blocking).
     *
     * @param bool $keepAlive Whether to keep the sudo session active by refreshing it every 60 seconds.
     * @param bool $silent    Whether to suppress the output of the sudo commands.
     * @param bool $verbose   Whether to display verbose output and error messages.
     *
     * @return int Returns the exit code of the sudo command (0 if successful).
     *
     * @throws RuntimeException If the initial sudo authentication fails or is cancelled by the user.
     */
    function sudoAuthenticate
    (
        ?CommandOptions $options = null ,
        bool $keepAlive          = false ,
        bool $silent             = false ,
        bool $verbose            = false
    ): int
    {
        $options = CommandOptions::resolve( $this->commandOptions ?? $options ) ;

        if( !$options->sudo )
        {
            if( $verbose )
            {
                $this->info( '[!] sudo is not required here.' . PHP_EOL ) ;
            }
            return ExitCode::SUCCESS ;
        }

        // Check if sudo is available on the system
        exec('command -v sudo' , $whichOutput , $whichReturn ) ;

        if ( $whichReturn !== ExitCode::SUCCESS )
        {
            throw new RuntimeException('sudo command not found on this system.' ) ;
        }

        // Prepare command options for silent and verbose output
        $redirectOut = $silent  ? ' > /dev/null 2>&1'       : '' ;
        $verboseMsg  = $verbose ? ' (verbose mode enabled)' : '' ;

        if ( $verbose )
        {
            $this->info(  "[*] Starting sudo authentication{$verboseMsg} ..." . PHP_EOL ) ;
        }

        // First, prompt for sudo password and cache credentials
        exec("sudo -v{$redirectOut}" , $output , $code ) ;

        if ( $code !== ExitCode::SUCCESS )
        {
            throw new RuntimeException('Sudo authentication failed or was cancelled.' ) ;
        }

        if ( $verbose )
        {
            $this->info( "[+] Sudo authentication succeeded." . PHP_EOL ) ;
        }

        // Optionally keep sudo session alive in the background
        if ( $keepAlive )
        {
            // Launch a background process to refresh sudo every 60 seconds
            $background = "nohup bash -c 'while true; do sudo -v; sleep 60; done'{$redirectOut} &" ;

            exec( $background , $bgOutput , $code ) ;

            if ( $code !== ExitCode::SUCCESS )
            {
                if ( $verbose )
                {
                    $this->warning( "[!] Failed to start background sudo keep-alive process." . PHP_EOL ) ;
                }
                return $code;
            }

            if ($verbose)
            {
                $this->info( "[+] Background sudo keep-alive process started." . PHP_EOL ) ;
            }
        }

        return ExitCode::SUCCESS ;
    }

    /**
     * Stops the background sudo keep-alive process if it is running.
     * @param bool $verbose Whether to output status messages.
     * @return bool True if the process was found and killed, false otherwise.
     */
    function sudoStopKeepAlive( bool $verbose = false ): bool
    {
        // Search the 'sudo -v' process in the background
        exec("pgrep -f 'sudo -v'", $pids ) ;

        if ( empty( $pids ) )
        {
            if ( $verbose )
            {
                $this->warning( "[*] No sudo keep-alive process found." . PHP_EOL ) ;
            }
            return false;
        }

        $success = true;
        foreach ( $pids as $pid )
        {
            exec("kill $pid" , $output , $code ) ;
            if ( $code !== 0 )
            {
                $success = false ;
                if ( $verbose )
                {
                    $this->warning( "[!] Failed to kill process $pid." . PHP_EOL ) ;
                }
            }
            else if ( $verbose )
            {
                $this->info( "[+] Killed sudo keep-alive process $pid." . PHP_EOL ) ;
            }
        }

        return $success ;
    }
}