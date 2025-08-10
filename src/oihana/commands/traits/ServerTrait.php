<?php

namespace oihana\commands\traits;

use oihana\commands\options\ServerOptions;

/**
 * Provides server-related functionality for commands
 * that rely on server configuration options.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait ServerTrait
{
    /**
     * The server options instance used by the command.
     *
     * @var ?ServerOptions Server options object, or null if not initialized.
     */
    public ?ServerOptions $serverOptions = null ;

    /**
     * Initializes the server options from a configuration array.
     *
     * If the array contains a `ServerOptions::SERVER` key, its value will be
     * used to initialize the server options. Otherwise, the full array is passed.
     *
     * @param array $init Configuration data used to create the ServerOptions instance.
     * @return static Returns the current instance for fluent chaining.
     */
    protected function initializeServerOptions( array $init = [] ) :static
    {
        $this->serverOptions = ServerOptions::create( $init[ ServerOptions::SERVER ] ?? $init ) ;
        return $this ;
    }
}