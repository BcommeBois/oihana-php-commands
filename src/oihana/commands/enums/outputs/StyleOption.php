<?php

namespace oihana\commands\enums\outputs;

use oihana\reflect\traits\ConstantsTrait;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Defines the supported text formatting options for Symfony Console output.
 * These constants can be used when creating custom styles via
 * {@see OutputFormatterStyle}.
 *
 * @package oihana\commands\enums\outputs
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
class StyleOption
{
    use ConstantsTrait ;

    /**
     * @var string The 'blink' text style.
     */
    public const string BLINK = 'blink' ;

    /**
     * @var string The 'bold' text style.
     */
    public const string BOLD = 'bold' ;

    /**
     * @var string The 'conceal' (hidden text) style.
     */
    public const string CONCEAL = 'conceal' ;

    /**
     * @var string The 'reverse' (swap foreground/background) style.
     */
    public const string REVERSE = 'reverse' ;

    /**
     * @var string The 'underscore' (underline text) style.
     */
    public const string UNDERSCORE = 'underscore' ;
}