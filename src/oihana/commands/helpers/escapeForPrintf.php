<?php

namespace oihana\commands\helpers;

/**
 * Escapes a string to be safely used inside a shell command with printf '%s' '...'.
 *
 * This function escapes single quotes in the input string by replacing each
 * `'` with `'\''`, so the string can be safely enclosed in single quotes
 * without breaking the shell syntax.
 *
 * @param string $content The input string to escape.
 *
 * @return string The escaped string enclosed in single quotes, safe for shell printf.
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\escapeForPrintf;
 *
 * $input = "It's a test string with 'single quotes'";
 * $escaped = escapeForPrintf($input);
 * // Result:  'It'\''s a test string with '\''single quotes'\'''
 *
 * // Example usage in a shell command:
 * $cmd = "printf '%s' $escaped";
 * echo $cmd;
 * // Outputs: printf '%s' 'It'\''s a test string with '\''single quotes'\'''
 * ```
 */
function escapeForPrintf( string $content ): string
{
    $escaped = str_replace("'"  , "'\\''" , $content );
    return "'" . $escaped . "'" ;
}
