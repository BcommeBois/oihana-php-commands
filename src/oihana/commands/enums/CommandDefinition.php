<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the command definitions keys.
 *
 * @package oihana\memcached\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandDefinition
{
    use ConstantsTrait ;

    const string APP_PATH    = 'appPath'    ;
    const string COMMAND     = 'command'    ;
    const string COMMANDS    = 'commands'   ;
    const string CONFIG      = 'config'     ;
    const string CONFIG_PATH = 'configPath' ;
}