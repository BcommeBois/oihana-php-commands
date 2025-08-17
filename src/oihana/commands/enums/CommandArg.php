<?php

namespace oihana\commands\enums;

use Closure;
use oihana\reflect\traits\ConstantsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Defines common command arguments used across console commands.
 *
 * This class centralizes the argument names (as constants) to avoid
 * magic strings in command implementations, and provides helper methods
 * to configure them consistently in {@see Command} definitions.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
class CommandArg
{
    use ConstantsTrait ;

    /**
     * Represents the "action" argument.
     *
     * This argument typically specifies the action or operation
     * the command should perform (e.g. "create", "delete", "update").
     */
    public const string ACTION = 'action' ;

    /**
     * Represents the "init" argument.
     *
     * This argument is usually used to indicate initialization parameters
     * or a setup mode for the command.
     */
    public const string INIT = 'init' ;

    /**
     * Configures the {@see CommandArg::ACTION} argument for a Symfony Console command.
     *
     * This helper method standardizes the definition of the "action" argument,
     * ensuring it is registered as a required argument with optional description,
     * default value, and suggested values.
     *
     * Example:
     * ```php
     * CommandArg::configureAction
     * (
     *     $command ,
     *     description : "The action to perform (e.g. create, update, delete)",
     *     suggestedValues : ["create", "update", "delete"]
     * );
     * ```
     *
     * @param Command       $command        The command to configure.
     * @param string|null   $description    An optional description of the argument (default: empty string).
     * @param mixed|null    $default        An optional default value (default: null).
     * @param array|Closure $suggestedValues Suggested values for autocompletion or validation.
     *                                      Can be a static array or a closure returning an array.
     *
     * @return Command The same {@see Command} instance, for fluent chaining.
     */
    public static function configureAction
    (
        Command       $command                ,
        ?string       $description     = ''   ,
        mixed         $default         = null ,
        array|Closure $suggestedValues = []   ,
    )
    :Command
    {
        $command->addArgument
        (
            name            : CommandArg::ACTION ,
            mode            : InputArgument::REQUIRED ,
            description     : $description ,
            default         : $default ,
            suggestedValues : $suggestedValues
        ) ;
        return $command ;
    }
}