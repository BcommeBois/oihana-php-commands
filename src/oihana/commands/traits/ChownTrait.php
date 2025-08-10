<?php

namespace oihana\commands\traits;

use RuntimeException;

use oihana\commands\enums\CommandParam;
use oihana\commands\enums\ExitCode;
use oihana\commands\options\ChownOptions;
use oihana\enums\Char;

use function oihana\files\getOwnershipInfos;

/**
 * Adds support for executing the `chown` command with customizable options,
 * either via direct arguments or an injected {@see ChownOptions} object.
 *
 * This trait is intended to be used within a command-handling context, and
 * requires a working implementation of `system()` (from {@see CommandTrait}).
 *
 * Features:
 * - Owner and group can be passed directly or via `ChownOptions`
 * - Supports sudo execution
 * - Optional strict mode to control validation behavior
 * - Graceful no-op execution when `strict=false` and conditions aren't met
 *
 * Example:
 * ```php
 * $this->chown('/var/www/html', 'www-data', null);
 * $this->chown('/var/www/example.com/www/htdocs', 'www-data', 'www-data' );
 * ```
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ChownTrait
{
    use CommandTrait ;

    /**
     * Key used in configuration arrays to refer to the `chown` section.
     */
    public const string CHOWN_COMMAND = 'chown' ;

    /**
     * Holds the `ChownOptions` instance if initialized or injected.
     * @var ChownOptions|null
     */
    public ?ChownOptions $chownOptions ;

    /**
     * Executes a `chown` operation on a given path.
     *
     * The owner and group may be specified directly or inferred from the given
     * {@see ChownOptions} instance or the trait's `$chownOptions` property.
     *
     * If `strict` is true, a {@see RuntimeException} is thrown when required
     * values (owner/group or path) are missing. Otherwise, the method returns
     * `ExitCode::SUCCESS` silently or with a warning if `verbose` is true.
     *
     * @param string|null             $path    Path to apply the ownership change to.
     * @param string|null             $owner   Owner user (e.g., `www-data`). Optional.
     * @param string|null             $group   Group name (e.g., `www-data`). Optional.
     * @param null|array|ChownOptions $options Optional options instance. If null, uses `$this->chownOptions`.
     * @param bool                    $silent  If true, suppresses system command output.
     * @param bool                    $verbose If true, displays warnings when skipping.
     * @param bool                    $strict  If true (default), throws on missing values.
     * @param ?bool                   $sudo    Enforce to use sudo if true.
     *
     * @return int `ExitCode::SUCCESS` (0) if the command runs or is skipped successfully.
     *
     * @throws RuntimeException    If required values are missing and `strict` is true.
     */
    public function chown
    (
        ?string                 $path    = null  ,
        ?string                 $owner   = null  ,
        ?string                 $group   = null  ,
        null|array|ChownOptions $options = null  ,
        bool                    $silent  = false ,
        bool                    $verbose = false ,
        bool                    $strict  = true  ,
        ?bool                   $sudo    = null
    )
    :int
    {
        $options = ChownOptions::resolve( $this->chownOptions , $options ) ;

        $path  = $path  ?? $options->path  ;
        $group = $group ?? $options->group ;
        $owner = $owner ?? $options->owner ;

        $current = getOwnershipInfos( $path ) ;

        $needChown = false;
        if ( $owner !== null && $current->owner !== $owner )
        {
            $needChown = true;
        }

        if ( $group !== null && $current->group !== $group )
        {
            $needChown = true;
        }

        if ( !$needChown )
        {
            if ( $verbose )
            {
                $this->info("Ownership of '$path' already matches: {$current->owner}:{$current->group}. Skipping chown." ) ;
            }
            return ExitCode::SUCCESS ;
        }

        if ( empty( $owner ) && empty( $group ) )
        {
            $message = 'You must provide at least an owner or a group for chown.' ;

            if( $strict )
            {
                throw new RuntimeException( $message ) ;
            }

            if( $verbose )
            {
                $this->warning( 'You must provide at least an owner or a group for chown.' ) ;
            }

            return ExitCode::SUCCESS ;
        }

        if( empty( $path ) )
        {
            $message = 'Missing `path` for chown operation.' ;

            if( $strict )
            {
                throw new RuntimeException($message ) ;
            }

            if( $verbose )
            {
                $this->warning( $message ) ;
            }

            return ExitCode::SUCCESS ;
        }

        $sudo = $sudo ?? $options->sudo ?? false ;
        $args = [ (string) $options ] ;

        // ----- owner:group

        $who = [] ;

        if( $owner !== null && $owner !== Char::EMPTY )
        {
            $who[] = $owner ;
        }

        if( $group !== null && $group !== Char::EMPTY )
        {
            $who[] = $group ;
        }

        if( count( $who ) > 0 )
        {
            $args[] = implode( Char::COLON , $who ) ;
        }

        // ----- path

        $args[] = $path ;

        // ----- Executes the command

        $this->system
        (
            command : self::CHOWN_COMMAND ,
            args    : $args ,
            silent  : $silent  ,
            verbose : $verbose ,
            sudo    : $sudo
        ) ;

        return ExitCode::SUCCESS ;
    }

    /**
     * Initializes the `$chownOptions` property from an input array.
     * @param array<string, mixed> $init Array of options, or an array containing the `'chown'` key.
     * @return static For method chaining.
     */
    protected function initializeChownOptions( array $init = [] ) :static
    {
        $this->chownOptions = new ChownOptions( $init[ CommandParam::CHOWN ] ?? $init ) ;
        return $this ;
    }
}