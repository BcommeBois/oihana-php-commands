<?php

namespace oihana\commands\enums;

use oihana\commands\enums\traits\CommandParamTrait;
use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the common command's parameters.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandParam
{
    use ConstantsTrait,
        CommandParamTrait ;
}