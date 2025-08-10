<?php

namespace oihana\commands\helpers;

/**
 * Clears the terminal console screen, depending on the operating system.
 *
 * On Windows systems, it runs `cls`; on Unix-based systems (Linux/macOS), it runs `clear`.
 * If `$clearable` is set to `false`, the function does nothing and returns `false`.
 *
 * > Note: The command relies on the system's availability of `cls` or `clear`.
 * > Use with caution in restricted or sandboxed environments.
 *
 * @param bool $clearable Whether or not to perform the console clear.
 *
 * @return false|string Returns the last line from the shell output, or false on failure or if `$clearable` is false.
 *
 * @example
 * ```php
 * clearConsole(); // Clears the screen
 * clearConsole(false); // Does nothing
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function clearConsole( bool $clearable = true ) :false|string
{
    if( $clearable )
    {
        $isWin = strncasecmp(PHP_OS , 'WIN' , 3 ) === 0 ;
        return $isWin ? system('cls') : system('clear') ;
    }
    return false ;
}
