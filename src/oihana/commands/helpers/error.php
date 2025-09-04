<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\StyleOption;

/**
 * Formats a message as an error message (red text, bold).
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "error" style.
 *
 * @param string $message The message text to format.
 *
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo error("Something went wrong!");
 * // Output: <fg=red;options=bold>Something went wrong!</>
 * ```
 */
function error( string $message ): string
{
    return format( $message, [ ColorParam::FG => Palette::RED, ColorParam::OPTIONS => [ StyleOption::BOLD ] ] ) ;
}