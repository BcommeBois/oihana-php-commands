<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The actions of the {@see \oihana\commands\cron\CronCommand}.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class CronAction
{
    use ConstantsTrait ;

    /**
     * Comments the line of a job out, without touching its definition.
     */
    public const string DISABLE = 'disable' ;

    /**
     * Uncomments the line of a job, without touching its definition.
     */
    public const string ENABLE = 'enable' ;

    /**
     * Shows the jobs of the scope, and what `sync` would do to each.
     */
    public const string LIST = 'list' ;

    /**
     * Removes the line of a job from the crontab.
     */
    public const string REMOVE = 'remove' ;

    /**
     * Shows what the crontab needs to match the jobs; writes it with `--apply`.
     */
    public const string SYNC = 'sync' ;
}
