<?php

namespace oihana\commands\options;

use oihana\options\Option;

/**
 * The enumeration of the global server options.
 *
 * @see ServerOptions
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ServerOption extends Option
{
    public const string DOMAIN    = 'domain'    ;
    public const string HTDOCS    = 'htdocs'    ;
    public const string PHP       = 'php'       ;
    public const string SUBDOMAIN = 'subdomain' ;
    public const string URL       = 'url'       ;
}