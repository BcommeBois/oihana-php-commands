<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the command config keys.
 *
 * @package oihana\memcached\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandConfig
{
    use ConstantsTrait ;

    public const string COMMAND  = 'command'  ;
    public const string ERRORS   = 'errors'   ;
    public const string TIMEZONE = 'timezone' ;
}