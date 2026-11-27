<?php

namespace oihana\commands\cron;

use InvalidArgumentException;

/**
 * What a crontab must become so that the lines of one scope match the jobs defined for it.
 *
 * The plan reads the lines of the crontab, keeps every line that is not of the scope exactly as
 * it is and where it is — comments, empty lines, variables such as `PATH=`, the jobs of other
 * tools and of other scopes —, and compares the lines of the scope with the jobs:
 *
 * - a job without a line is **added**, after the last line of the scope, or at the end of the
 *   crontab when the scope has none yet ;
 * - a line that differs from its job only by being commented out, or not, is **enabled** or
 *   **disabled** ;
 * - a line that differs otherwise is **updated** in place ;
 * - a line of the scope no job is defined for, and every line after the first one of the same
 *   job, is **removed**.
 *
 * Building the plan writes nothing: {@see CrontabPlan::$lines} is the crontab to write, and
 * {@see CrontabPlan::$changes} what writing it changes. A plan built on the lines of another
 * plan holds no change.
 *
 * @example
 * ```php
 * use oihana\commands\cron\CronJob;
 * use oihana\commands\cron\Crontab;
 * use oihana\commands\cron\CrontabPlan;
 *
 * $crontab = new Crontab() ;
 * $plan    = new CrontabPlan( 'myapp' , [ new CronJob( 'check' , '0 6 * * *' , '/srv/app/check.sh' ) ] , $crontab->read() ) ;
 *
 * if( !$plan->isEmpty() )
 * {
 *     $crontab->write( $plan->lines ) ;
 * }
 * ```
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final readonly class CrontabPlan
{
    /**
     * Compares the lines of a crontab with the jobs of a scope.
     *
     * @param string            $scope The scope of the jobs.
     * @param iterable<CronJob> $jobs  The jobs the scope must hold.
     * @param list<string>      $lines The lines of the crontab, as {@see Crontab::read()} gives them.
     *
     * @throws InvalidArgumentException When the scope is not an identifier, or two jobs share a name.
     */
    public function __construct( string $scope , iterable $jobs , array $lines )
    {
        if( !CronLine::isIdentifier( $scope ) )
        {
            throw new InvalidArgumentException( sprintf
            (
                'The cron scope "%s" is not valid: use letters, digits, "-" and "_", starting with a letter or a digit.' ,
                $scope
            )) ;
        }

        $defined = [] ;

        foreach( $jobs as $job )
        {
            if( isset( $defined[ $job->name ] ) )
            {
                throw new InvalidArgumentException( sprintf( 'Two cron jobs of the scope "%s" are named "%s".' , $scope , $job->name ) ) ;
            }

            $defined[ $job->name ] = $job ;
        }

        $changes   = [] ;
        $installed = [] ;
        $result    = [] ;
        $last      = null ;

        foreach( $lines as $line )
        {
            $name = CronLine::name( $line , $scope ) ;

            if( $name === null )
            {
                $result[] = $line ;
                continue ;
            }

            if( isset( $installed[ $name ] ) || !isset( $defined[ $name ] ) )
            {
                $changes[] = new CronChange( CronChange::REMOVE , $name , $line ) ;
                continue ;
            }

            $installed[ $name ] = $line ;

            $job      = $defined[ $name ] ;
            $expected = $job->line( $scope ) ;

            if( $line !== $expected )
            {
                $type = CronLine::enable( $line ) === CronLine::enable( $expected )
                      ? ( $job->enabled ? CronChange::ENABLE : CronChange::DISABLE )
                      : CronChange::UPDATE ;

                $changes[] = new CronChange( $type , $name , $line , $expected ) ;
            }

            $result[] = $expected ;
            $last     = count( $result ) - 1 ;
        }

        $added = [] ;

        foreach( $defined as $name => $job )
        {
            if( !isset( $installed[ $name ] ) )
            {
                $line      = $job->line( $scope ) ;
                $added[]   = $line ;
                $changes[] = new CronChange( CronChange::ADD , $name , null , $line ) ;
            }
        }

        if( $added !== [] )
        {
            array_splice( $result , $last === null ? count( $result ) : $last + 1 , 0 , $added ) ;
        }

        $this->changes   = $changes ;
        $this->installed = $installed ;
        $this->jobs      = $defined ;
        $this->lines     = $result ;
        $this->scope     = $scope ;
    }

    /**
     * What writing {@see CrontabPlan::$lines} changes, in the order of the crontab, the additions last.
     *
     * @var list<CronChange>
     */
    public array $changes ;

    /**
     * The line each job of the scope has in the crontab now, by job name — the first one, when a job has several.
     *
     * @var array<string, string>
     */
    public array $installed ;

    /**
     * The jobs of the scope, by name.
     *
     * @var array<string, CronJob>
     */
    public array $jobs ;

    /**
     * The crontab to write.
     *
     * @var list<string>
     */
    public array $lines ;

    /**
     * The scope of the jobs.
     *
     * @var string
     */
    public string $scope ;

    /**
     * The change the plan makes to a job, if any.
     *
     * @param string $name The name of the job.
     *
     * @return CronChange|null The change, or null when the line of the job stays as it is.
     */
    public function change( string $name ) :?CronChange
    {
        foreach( $this->changes as $change )
        {
            if( $change->name === $name )
            {
                return $change ;
            }
        }

        return null ;
    }

    /**
     * Tells whether the crontab already matches the jobs.
     *
     * @return bool True when writing the plan would change nothing.
     */
    public function isEmpty() :bool
    {
        return $this->changes === [] ;
    }
}
