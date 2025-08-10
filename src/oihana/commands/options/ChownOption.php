<?php

namespace oihana\commands\options;

use oihana\options\Option;

use function oihana\core\strings\hyphenate;
use function oihana\files\isMac;

/**
 * The enumeration of the chown command options.
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ChownOption extends Option
{
    public const string FROM           = 'from' ;
    public const string GROUP          = 'group' ;
    public const string NO_DEREFERENCE = 'noDereference' ;
    public const string OWNER          = 'owner' ;
    public const string PATH           = 'path' ;
    public const string REFERENCE      = 'reference' ;
    public const string RECURSIVE      = 'recursive' ;
    public const string SUDO           = 'sudo' ;
    public const string VERBOSE        = 'verbose'  ;

    public static function getCommandOption(string $option): string
    {
        // macOS: BSD chown → short options
        if ( isMac() )
        {
            return match ($option)
            {
                self::RECURSIVE      => 'R' ,
                self::VERBOSE        => 'v' ,
                default              => ''  , // GROUP, OWNER, PATH are positional
            };
        }

        // Linux: GNU chown → long options
        return hyphenate( $option ) ;
    }
}