<?php

namespace oihana\commands\cron;

/**
 * One change a {@see CrontabPlan} makes to a crontab: a line added, updated, removed, enabled or disabled.
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final readonly class CronChange
{
    /**
     * Creates a change.
     *
     * @param string      $type   One of the constants of this class.
     * @param string      $name   The name of the job.
     * @param string|null $before The line before the change, or null for a line added.
     * @param string|null $after  The line after the change, or null for a line removed.
     */
    public function __construct
    (
        public string  $type ,
        public string  $name ,
        public ?string $before = null ,
        public ?string $after  = null ,
    )
    {
    }

    /**
     * A job the crontab does not hold yet: its line is added.
     */
    public const string ADD = 'add' ;

    /**
     * A job active in the crontab and disabled in its definition: its line is commented out.
     */
    public const string DISABLE = 'disable' ;

    /**
     * A job commented out in the crontab and enabled in its definition: its line is uncommented.
     */
    public const string ENABLE = 'enable' ;

    /**
     * A line of the scope no job is defined for any more, or a second line of the same job: it is removed.
     */
    public const string REMOVE = 'remove' ;

    /**
     * A job whose schedule, command or log changed: its line is rewritten.
     */
    public const string UPDATE = 'update' ;
}
