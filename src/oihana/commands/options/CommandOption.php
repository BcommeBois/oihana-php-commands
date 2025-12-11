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
    public const string CLEAR          = 'clear'      ;
    public const string CONFIG         = 'config'     ;
    public const string DIR            = 'dir'        ;
    public const string DECRYPT        = 'decrypt'    ;
    public const string ENCRYPT        = 'encrypt'    ;
    public const string ENV            = 'env'        ;
    public const string FORCE          = 'force'      ;
    public const string FORCE_SHORTCUT = 'f'          ;
    public const string PASS_PHRASE    = 'passphrase' ;
    public const string QUIET          = 'quiet'      ;
    public const string SILENT         = 'silent'     ;
    public const string SUDO           = 'sudo'       ;
    public const string OWNER          = 'owner'      ;
    public const string VERSION        = 'version'    ;

    /**
     * Configures the 'clear' option of the current command.
     *
     * @param Command     $command     The command reference to configure.
     * @param bool        $hasClear    Indicates if the clear option is configured.
     * @param string|null $shortcut    The optional shortcut of the command.
     * @param string      $description The description of the option.
     *
     * @return Command
     */
    public static function configureClear
    (
        Command $command ,
        bool    $hasClear    = true ,
       ?string  $shortcut    = null ,
        string  $description = 'Clear the console.'
    )
    : Command
    {
        if( $hasClear )
        {
            $command->addOption
            (
                name        : self::CLEAR ,
                shortcut    : $shortcut ,
                mode        : InputOption::VALUE_NONE ,
                description : $description
            ) ;
        }
        return $command ;
    }

    /**
     * Configures the 'env' option of the current command.
     *
     * @param Command     $command     The command reference to configure.
     * @param bool        $hasEnv      Indicates if the 'env' option is configured.
     * @param string|null $shortcut    The optional shortcut of the command.
     * @param string      $description The description of the option.
     *
     * @return Command
     */
    public static function configureEnv
    (
        Command $command ,
        bool    $hasEnv     = true ,
        ?string $shortcut   = null ,
        string  $description = 'Set the environment option of the command.'
    )
    : Command
    {
        if( $hasEnv )
        {
            $command->addOption
            (
                name        : self::ENV ,
                shortcut    : $shortcut ,
                mode        : InputOption::VALUE_OPTIONAL ,
                description : $description
            ) ;
        }
        return $command ;
    }

    /**
     * Configures the 'force' option of the current command.
     *
     * @param Command     $command     The command reference to configure.
     * @param bool        $hasForce    Indicates if the force option is configured.
     * @param string|null $shortcut    The optional shortcut of the command.
     * @param string      $description The description of the option.
     *
     * @return Command
     */
    public static function configureForce
    (
        Command $command ,
        bool    $hasForce    = true ,
        ?string  $shortcut   = null ,
        string  $description = 'Force the command.'
    )
    : Command
    {
        if( $hasForce )
        {
            $command->addOption
            (
                name        : self::FORCE ,
                shortcut    : $shortcut ,
                mode        : InputOption::VALUE_NONE ,
                description : $description
            ) ;
        }
        return $command ;
    }
}