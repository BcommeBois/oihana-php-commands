<?php

namespace oihana\commands\exceptions;

use Exception;
use Throwable;

/**
 * The missing passphrase exception.
 *
 * @package oihana\exceptions
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class MissingPassphraseException extends Exception
{
    /**
     * Creates a new MissingPassphraseException instance.
     * @param string  $message The message of the error.
     * @param int $code The code of the error.
     * @param Throwable|null $previous
     */
    public function __construct( string $message = "The passphrase is required.", int $code = 0 , ?Throwable $previous = null )
    {
        parent::__construct( $message , $code , $previous ) ;
    }
}