<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;

/**
 * Formats a message as a success message (green text).
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "success" style.
 *
 * @param string $message The message text to format.
 *
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo success("Operation completed successfully!");
 * // Output: <fg=green>Operation completed successfully!</>
 * ```
 */
function success( string $message ) :string
{
    return format( $message , [ ColorParam::FG => Palette::GREEN ] ) ;
}