<?php

namespace oihana\commands\enums;

use oihana\reflections\traits\ConstantsTrait;

/**
 * The enumeration of the command's helpers.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandHelper
{
    use ConstantsTrait ;

    public const string QUESTION = 'question' ;
}