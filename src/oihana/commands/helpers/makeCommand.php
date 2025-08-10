<?php

namespace oihana\commands\helpers;

use oihana\commands\options\CommandOptions;
use oihana\enums\Char;

/**
 * Generates a complete shell command string, optionally including a pipeline.
 *
 * This function builds a shell command from a central main command, optional
 * arguments/options, and optional commands to prepend (`$previous`) or append (`$post`)
 * in a pipeline (`|`).
 *
 * The main command can be provided as a string or an array (which will be joined
 * by spaces). Arguments and options can also be strings or arrays of strings.
 *
 * The `$options` parameter allows adding global prefixes such as `sudo` or running
 * the command as another user.
 *
 * If the main command is null, only the pipeline between `$previous` and `$post`
 * is generated, and arguments or options are ignored.
 *
 * @param array<string>|string|null $command  The main command to run, either as a string (e.g. 'ls')
 *                                            or an array of parts (e.g. ['wp', 'post', 'list']).
 * @param array<string>|string|null $args     Arguments and options for the main command (e.g. '-la' or ['--format=ids']).
 * @param CommandOptions|null       $options  Global command options such as sudo or user context.
 * @param string|null               $previous Command to prepend before the main command, for pipelines (e.g. 'cat file.txt').
 * @param string|null               $post     Command to append after the main command, for pipelines (e.g. 'grep "error"').
 *
 * @return string The final full command string to execute, including any pipelines.
 *
 * @example
 *
 * 1. Simple command with arguments
 * ```php
 * $cmd = $this->makeCommand( 'ls' , '-la' ) ;
 * // Outputs: "ls -la"
 * ```
 *
 * 2. Command as an array (safer against shell injection)
 * ```php
 * $cmd2 = $this->makeCommand(['wp', 'post', 'list'], '--format=ids');
 * // Outputs: "wp post list --format=ids"
 * ``
 *
 * 3. Command with options (sudo/user)
 * ```php
 * $options = new CommandOptions(['sudo' => true, 'user' => 'www-data']);
 * $cmd     = $this->makeCommand('wp cache flush', options: $options );
 * // Outputs: "sudo -u www-data wp cache flush"
 * ```
 *
 * 4. Full pipeline: read a file, filter it, then count lines
 * ```php
 * $cmd = $this->makeCommand( 'grep "error"', previous: 'cat /var/log/syslog', post: 'wc -l');
 * // Outputs: "cat /var/log/syslog | grep "error" | wc -l"
 * ```
 *
 * 5. Pipeline without a central command (use case for $command = null)
 * ```php
 * $cmd = $this->makeCommand( previous: 'ls -1 /var/www', post: 'wc -l');
 * // Outputs: "ls -1 /var/www | wc -l"
 * ```
 *
 * 6. Arguments and options are logically ignored if the command is null
 * ```php
 * $cmd = $this->makeCommand(null, '--force', previous: 'echo "start"');
 * // Outputs: "echo "start"" (options and arguments are not added)
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function makeCommand
(
    null|array|string $command ,
    null|array|string $args     = null ,
    ?CommandOptions   $options  = null ,
    ?string           $previous = null  ,
    ?string           $post     = null  ,
)
: string
{
    $pipelineParts = [] ;

    if ( !empty( $previous ) )
    {
        $pipelineParts[] = $previous ;
    }

    if ( !empty( $command ) )
    {
        if ( is_array( $command ) )
        {
            $command = implode
            (
                Char::SPACE ,
                array_filter( $command , fn ($v) => $v !== null && trim($v) !== '' )
            ) ;
        }

        $mainCommandParts = [] ;

        if( isset( $options ) )
        {
            $mainCommandParts[] = (string) $options ;
        }

        $mainCommandParts[] = $command ;

        if( is_array( $args ) && count( $args ) > 0 )
        {
            $args = array_filter( $args , fn($v) => $v !== null && trim ( $v ) !== '' )  ;
            $args = implode( Char::SPACE , $args ) ;
        }

        $args = trim( (string) $args ) ;
        if( $args != Char::EMPTY )
        {
            $mainCommandParts[] = $args ;
        }

        $mainCommand = implode(Char::SPACE , $mainCommandParts ) ;

        $pipelineParts[] = $mainCommand ;
    }

    if ( !empty( $post ) )
    {
        $pipelineParts[] = $post ;
    }

    return trim( implode( ' | ' , $pipelineParts ) ) ;
}