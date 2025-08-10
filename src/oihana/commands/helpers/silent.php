<?php

namespace oihana\commands\helpers;

/**
 * Appends a redirection string to suppress output from a shell command.
 *
 * This helper function modifies the given command string in-place by appending
 * a platform-appropriate silence directive:
 * - On Unix/Linux/macOS: `> /dev/null 2>&1`
 * - On Windows:          `> NUL 2>&1`
 *
 * This is typically used to hide output when executing shell commands.
 *
 * @param ?string $command The command string to modify by reference.
 * @param bool $silent Whether to append the silence directive to the command.
 *
 * @return ?string The updated command string (or null if input was null).
 *
 * @example
 * ```php
 * $cmd = 'wp plugin install hello-dolly';
 * silent($cmd, true);
 * echo $cmd;
 * // Output (Unix)    : wp plugin install hello-dolly > /dev/null 2>&1
 * // Output (Windows) : wp plugin install hello-dolly > NUL 2>&1
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function silent( ?string &$command, bool $silent = false ) :?string
{
    if ( $silent && $command !== null && $command !== '')
    {
        $redirect = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN'
                  ? ' > NUL 2>&1'
                  : ' > /dev/null 2>&1';

        $command .= $redirect ;
    }
    return $command ;
}
