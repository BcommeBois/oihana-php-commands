<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;

/**
 * Formats a message as an informational message using a cyan foreground color.
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "info" style.
 *
 * @param string $message The message text to format.
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo info("This is an informational message");
 * // Output: <fg=cyan>This is an informational message</>
 * ```
 */
function info( string $message ) :string
{
    return format( $message, [ ColorParam::FG => Palette::CYAN ] ) ;
}