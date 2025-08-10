<?php

namespace oihana\commands\exceptions ;

use Exception;
use oihana\exceptions\ExceptionTrait;

/**
 * Invoked when the user won't exist a command.
 *
 * @package oihana\commands\exceptions
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ExitException extends Exception
{
    use ExceptionTrait ;
}