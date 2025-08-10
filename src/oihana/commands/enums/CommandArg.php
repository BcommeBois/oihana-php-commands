<?php

namespace oihana\commands\enums;

use oihana\reflections\traits\ConstantsTrait;

/**
 * The common command arguments enumeration.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandArg
{
    use ConstantsTrait ;

    /**
     * The 'action' argument.
     */
    public const string ACTION = 'action' ;
}