<?php

namespace oihana\commands\helpers;

use InvalidArgumentException;

use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\StyleOption;

/**
 * Formats a message string with Symfony Console color/style tags.
 *
 * This function generates a string decorated with Symfony Console
 * formatting tags (`<fg=...;bg=...;options=...>message</>`) based
 * on the provided foreground, background, and style options.
 *
 * Supported options keys:
 * - `fg`       : foreground color (see {@see Palette}).
 * - `bg`       : background color (see {@see Palette}).
 * - `options`  : one or multiple text styles (see {@see StyleOption}).
 *
 * @param string $message  The text to format.
 * @param array  $options  Optional associative array of formatting options.
 *
 * @return string The message wrapped in Symfony Console style tags.
 *
 * @throws InvalidArgumentException If an invalid color or style is provided.
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\format;
 * use oihana\commands\enums\outputs\Palette;
 * use oihana\commands\enums\outputs\StyleOption;
 *
 * echo format('Hello World', ['fg' => Palette::GREEN]);
 * // Output: <fg=green>Hello World</>
 *
 * echo format('Warning!', ['fg' => Palette::RED, 'options' => StyleOption::BOLD]);
 * // Output: <fg=red;options=bold>Warning!</>
 *
 * echo format('Info', ['bg' => Palette::CYAN, 'options' => [StyleOption::UNDERSCORE, StyleOption::BOLD]]);
 * // Output: <bg=cyan;options=underscore,bold>Info</>
 * ```
 */
function format( string $message , array $options = [] ) :string
{
    $fg     = $options[ ColorParam::FG      ] ?? $options[ ColorParam::FOREGROUND ] ?? null ;
    $bg     = $options[ ColorParam::BG      ] ?? $options[ ColorParam::BACKGROUND ] ?? null ;
    $styles = $options[ ColorParam::OPTIONS ] ?? [] ;

    $parts = [] ;

    if ( !empty( $fg ) )
    {
        Palette::assertColor( $fg ) ;
        $parts[] = 'fg=' . $fg ;
    }

    if ( !empty( $bg ) )
    {
        Palette::assertColor( $bg ) ;
        $parts[] = 'bg=' . $bg ;
    }

    if ( !empty( $styles ) )
    {
        if (is_string( $styles ) )
        {
            $styles = explode(',' , $styles ) ;
        }

        if ( !is_array( $styles ) )
        {
            throw new InvalidArgumentException( "Invalid 'options' format. Expected string or array." ) ;
        }

        $validStyles = [] ;
        foreach ( $styles as $style )
        {
            if ( !StyleOption::includes( $style ) )
            {
                throw new InvalidArgumentException
                (
                    sprintf("Invalid style option '%s'. Allowed values: %s", $style, implode(', ' , StyleOption::enums() ) )
                );
            }
            $validStyles[] = $style ;
        }

        if ( !empty( $validStyles ) )
        {
            $parts[] = 'options=' . implode(',', $validStyles ) ;
        }
    }

    if ( empty( $parts ) )
    {
        return $message;
    }

    return '<' . implode(';', $parts) . '>' . $message . '</>' ;
}