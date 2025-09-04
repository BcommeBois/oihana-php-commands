<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\StyleOption;

/**
 * Formats a message as a comment (magenta text, underscore).
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "comment" style.
 *
 * @param string $message The message text to format.
 *
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo comment("This is a comment");
 * // Output: <fg=magenta;options=underscore>This is a comment</>
 * ```
 */
function comment( string $message ): string
{
    return format( $message, [ ColorParam::FG => Palette::MAGENTA , ColorParam::OPTIONS => [ StyleOption::UNDERSCORE ] ] ) ;
}