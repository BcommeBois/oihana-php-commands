<?php

namespace oihana\commands\options;

use oihana\enums\Char;
use oihana\options\Options;

/**
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandOptions extends Options
{
    /**
     * The 'command' parameter.
     */
    public const string COMMAND = 'command' ;

    /**
     * Indicates if the command clear the terminal when is launched.
     * @var bool
     */
    public bool $clear = false ;

    /**
     * The path of the config directory.
     * @var ?string
     */
    public ?string $config = '' ;

    /**
     * The path of the command directory.
     * @var ?string
     */
    public ?string $dir = '' ;

    /**
     * Whether to prefix the command with `sudo`.
     * @var bool|null
     */
    public ?bool $sudo = false ;

    /**
     * Set the owner user to run the command as, if sudo is enabled.
     * @var string|null
     */
    public ?string $owner = null ;

    /**
     * Builds a command-line string of options based on the current object state.
     *
     * @param class-string $clazz Class implementing the getCommandOption(string $property): string method.
     * @param null|callable|string $prefix Prefix for each option (e.g. '--', '-', '/opt:'), or a callable (string $property): string.
     * @param array<string> $excludes Optional list of property names to exclude from the output.
     * @param callable|string $separator Separator between option and value (default is a space), or a callable (string $property): string.
     * @param array<string> $order Optional list of property names to force order.
     * @param bool $reverseOrder If true, ordered properties are placed at the end instead of the beginning.
     *
     * @return string CLI-formatted options string, e.g. '--foo "bar" -v --list "one" --list "two"'
     *
     * @return string
     */
    public function getOptions
    (
        ?string              $clazz        = null ,
        callable|string|null $prefix       = Char::DOUBLE_HYPHEN ,
        ?array               $excludes     = null ,
        callable|string      $separator    = Char::SPACE ,
        ?array               $order        = null ,
        bool                 $reverseOrder = false
    )
    :string
    {
        if( $this->sudo === true )
        {
            $parts[] = 'sudo' ;
            $owner = is_string( $this->owner ) ? trim( $this->owner ) : Char::EMPTY ;
            if( $owner != Char::EMPTY )
            {
                $parts[] = '-u ' . $owner ;
            }
            return implode(Char::SPACE , $parts ) ;
        }
        return Char::EMPTY ;
    }

    /**
     * Returns the string expression of the object.
     * @return string
     */
    public function __toString() : string
    {
        return $this->getOptions() ;
    }
}