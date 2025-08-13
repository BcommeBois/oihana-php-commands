<?php

namespace oihana\commands\options;

use oihana\options\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * The enumeration of the global command options.
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandOption extends Option
{
    public const string CLEAR   = 'clear'    ;
    public const string CONFIG  = 'config'   ;
    public const string DIR     = 'dir'      ;
    public const string SUDO    = 'sudo'     ;
    public const string OWNER   = 'owner'    ;

    /**
     * Configures the options of the current command.
     *
     * @param Command $command  The command reference to configure.
     * @param bool    $hasClear Indicates if the clear option is configured.
     *
     * @return Command
     */
    public static function configure
    (
        Command $command ,
        bool    $hasClear = true
    )
    : Command
    {
        if( $hasClear )
        {
            $command->addOption
            (
                name        : self::CLEAR ,
                shortcut    : 'c' ,
                mode        : InputOption::VALUE_NONE ,
                description :  'Clear the console.'
            ) ;
        }
        return $command ;
    }
}