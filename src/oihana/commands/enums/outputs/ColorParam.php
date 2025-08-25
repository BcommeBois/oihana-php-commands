<?php

namespace oihana\commands\enums\outputs;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Defines the available style attributes for Symfony Console output.
 *
 * @package oihana\commands\enums\outputs
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
class ColorParam
{
    use ConstantsTrait ;

    /**
     * @var string Foreground color key
     */
    public const string FOREGROUND = 'foreground';

    /**
     * @var string Background color key
     */
    public const string BACKGROUND = 'background';

    /**
     * @var string Options / styles key
     */
    public const string OPTIONS = 'options';
}