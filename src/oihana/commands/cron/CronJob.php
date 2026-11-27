<?php

namespace oihana\commands\cron;

use InvalidArgumentException;

/**
 * A job a crontab must hold: when it runs, what it runs, whether it is enabled, where it writes.
 *
 * Everything is checked when the job is built, so that a job that exists can always be written:
 *
 * - the name is an identifier ({@see CronLine::isIdentifier()}) — it tells the job apart in its scope ;
 * - the schedule is valid ({@see CronSchedule::isValid()}) ;
 * - the command is one line, and holds no unescaped `%`: cron turns `%` into a new line, so
 *   `date +%F` must be written `date +\%F` ;
 * - the log, when given, is an absolute path without `%`: cron runs from the home directory of
 *   its user, and a relative path would write there.
 *
 * Cron starts its jobs with a minimal environment: a command is written with absolute paths
 * (`/usr/bin/php /srv/app/bin/console.php …`), never relying on the profile of the user.
 *
 * @example
 * ```php
 * use oihana\commands\cron\CronJob;
 *
 * $job = new CronJob
 * (
 *     name     : 'check' ,
 *     schedule : '0 6 * * 1-6' ,
 *     command  : '/usr/bin/php /srv/app/bin/console.php app:check' ,
 *     log      : '/srv/app/logs/cron/check.log' ,
 * ) ;
 *
 * echo $job->line( 'myapp' ) ;
 * // 0 6 * * 1-6 /usr/bin/php /srv/app/bin/console.php app:check >> '/srv/app/logs/cron/check.log' 2>&1 # oihana-cron:myapp:check
 * ```
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final readonly class CronJob
{
    /**
     * Creates a job, and checks it.
     *
     * @param string      $name     The name of the job, unique in its scope.
     * @param string      $schedule The schedule: five fields, or a macro such as `@daily`.
     * @param string      $command  The command, on one line.
     * @param bool        $enabled  False to keep the line in the crontab, commented out.
     * @param string|null $log      The absolute path of a file the output of the job is appended to.
     *
     * @throws InvalidArgumentException When a value cannot be written in a crontab.
     */
    public function __construct
    (
        public string  $name ,
        public string  $schedule ,
        public string  $command ,
        public bool    $enabled = true ,
        public ?string $log     = null ,
    )
    {
        if( !CronLine::isIdentifier( $name ) )
        {
            throw new InvalidArgumentException( sprintf
            (
                'The cron name "%s" is not valid: use letters, digits, "-" and "_", starting with a letter or a digit.' ,
                $name
            )) ;
        }

        if( !CronSchedule::isValid( $schedule ) )
        {
            throw new InvalidArgumentException( sprintf( 'The schedule "%s" of the cron job "%s" is not valid.' , $schedule , $name ) ) ;
        }

        if( trim( $command ) === '' || preg_match( '/[\r\n]/' , $command ) === 1 )
        {
            throw new InvalidArgumentException( sprintf( 'The command of the cron job "%s" must be one line, not empty.' , $name ) ) ;
        }

        if( preg_match( '/(?<!\\\\)%/' , $command ) === 1 )
        {
            throw new InvalidArgumentException( sprintf
            (
                'The command of the cron job "%s" holds an unescaped "%%": cron turns it into a new line. Write "\\%%" instead.' ,
                $name
            )) ;
        }

        if( $log !== null && ( !str_starts_with( $log , '/' ) || preg_match( '/[\r\n%]/' , $log ) === 1 ) )
        {
            throw new InvalidArgumentException( sprintf
            (
                'The log of the cron job "%s" must be an absolute path, without "%%" and on one line: "%s".' ,
                $name ,
                $log
            )) ;
        }
    }

    /**
     * The key of the command in the definition of a job.
     */
    public const string COMMAND = 'command' ;

    /**
     * The key of the enabled flag in the definition of a job.
     */
    public const string ENABLED = 'enabled' ;

    /**
     * The key of the log in the definition of a job.
     */
    public const string LOG = 'log' ;

    /**
     * The key of the name in the definition of a job.
     */
    public const string NAME = 'name' ;

    /**
     * The key of the schedule in the definition of a job.
     */
    public const string SCHEDULE = 'schedule' ;

    /**
     * Creates a job from its definition, as a configuration file gives it.
     *
     * An empty log is no log. A missing `enabled` is true.
     *
     * @param array<mixed> $definition The keys `name`, `schedule`, `command`, and optionally `enabled` and `log`.
     *
     * @return CronJob The job.
     *
     * @throws InvalidArgumentException When a key is missing or a value cannot be written in a crontab.
     */
    public static function fromArray( array $definition ) :CronJob
    {
        foreach( [ self::NAME , self::SCHEDULE , self::COMMAND ] as $key )
        {
            if( !is_string( $definition[ $key ] ?? null ) )
            {
                throw new InvalidArgumentException( sprintf
                (
                    'A cron job needs a "%s" string: %s' ,
                    $key ,
                    json_encode( $definition , JSON_UNESCAPED_SLASHES )
                )) ;
            }
        }

        $log = $definition[ self::LOG ] ?? null ;

        return new CronJob
        (
            name     : $definition[ self::NAME     ] ,
            schedule : $definition[ self::SCHEDULE ] ,
            command  : $definition[ self::COMMAND  ] ,
            enabled  : (bool) ( $definition[ self::ENABLED ] ?? true ) ,
            log      : is_string( $log ) && $log !== '' ? $log : null ,
        ) ;
    }

    /**
     * The line of the job in the crontab, with its marker; commented out when the job is disabled.
     *
     * The fields of the schedule are separated by one space, whatever the definition held.
     *
     * @param string $scope The scope the line belongs to.
     *
     * @return string The line.
     *
     * @throws InvalidArgumentException When the scope is not an identifier.
     */
    public function line( string $scope ) :string
    {
        $line = implode( ' ' , preg_split( '/\s+/' , trim( $this->schedule ) ) ?: [] ) . ' ' . trim( $this->command ) ;

        if( $this->log !== null )
        {
            $line .= ' >> ' . escapeshellarg( $this->log ) . ' 2>&1' ;
        }

        $line .= ' ' . CronLine::marker( $scope , $this->name ) ;

        return $this->enabled ? $line : CronLine::disable( $line ) ;
    }
}
