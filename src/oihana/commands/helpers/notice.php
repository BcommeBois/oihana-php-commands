<?php

namespace oihana\commands\helpers;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\StyleOption;

/**
 * Formats a message as a notice (blue text, bold).
 *
 * This is a helper function based on {@see format()} to quickly generate
 * console output messages with a consistent "notice" style.
 *
 * @param string $message The message text to format.
 * @return string The formatted message with Symfony Console style tags.
 *
 * @example
 * ```php
 * echo notice("Take note of this!");
 * // Output: <fg=blue;options=bold>Take note of this!</>
 * ```
 */
function notice(string $message): string
{
    return format($message, [ColorParam::FG => Palette::BLUE, ColorParam::OPTIONS => [StyleOption::BOLD]]);
}