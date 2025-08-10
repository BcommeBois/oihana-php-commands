<?php

namespace oihana\commands\options;

/**
 * The sudo command options definition.
 *
 * The `sudo` property is true by default.
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class SudoCommandOptions extends CommandOptions
{
    /**
     * Whether to prefix the command with `sudo`.
     * @var bool|null
     */
    public ?bool $sudo = true ;
}