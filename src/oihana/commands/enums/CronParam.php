<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The initialization parameters of the {@see \oihana\commands\cron\CronCommand}.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class CronParam
{
    use ConstantsTrait ;

    /**
     * The directory the crontab is copied to before each write. Without it, nothing is copied.
     */
    public const string BACKUPS = 'backups' ;

    /**
     * The {@see \oihana\commands\cron\Crontab} to work on; a new one by default.
     */
    public const string CRONTAB = 'crontab' ;

    /**
     * The jobs of the scope: a list of {@see \oihana\commands\cron\CronJob}, or of their definitions.
     */
    public const string JOBS = 'jobs' ;

    /**
     * How many copies of the crontab to keep in the backup directory.
     */
    public const string KEEP = 'keep' ;

    /**
     * The scope of the jobs: the lines of the crontab that belong to this command.
     */
    public const string SCOPE = 'scope' ;
}
