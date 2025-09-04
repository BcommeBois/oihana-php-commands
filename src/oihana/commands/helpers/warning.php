<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;

/**
 * Formats a message as a warning message (yellow text).
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "warning" style.
 *
 * @param string $message The message text to format.
 *
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo warning("Be careful!");
 * // Output: <fg=yellow>This is a warning message</>
 * ```
 */
function warning( string $message ) :string
{
    return format( $message , [ ColorParam::FG => Palette::YELLOW ] ) ;
}