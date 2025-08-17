<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

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

    /**
     * The 'action' argument.
     */
    public const string ENCRYPT = 'encrypt' ;

    /**
     * The 'init' argument.
     */
    public const string INIT = 'init' ;
}