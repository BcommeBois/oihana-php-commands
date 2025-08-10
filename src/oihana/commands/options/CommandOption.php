<?php

namespace oihana\commands\options;

use oihana\options\Option;

/**
 * The enumeration of the global command options.
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandOption extends Option
{
    public const string CERTBOT = 'certbot'  ;
    public const string CLEAR   = 'clear'    ;
    public const string CONFIG  = 'config'   ;
    public const string DIR     = 'dir'      ;
    public const string NGINX   = 'nginx'    ;
    public const string SUDO    = 'sudo'     ;
    public const string OWNER   = 'owner'    ;
}