<?php

namespace oihana\commands\cron;

use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use oihana\commands\enums\CommandArg;
use oihana\commands\enums\CronAction;
use oihana\commands\enums\CronParam;
use oihana\commands\enums\ExitCode;
use oihana\commands\Kernel;
use oihana\commands\traits\LockCommandTrait;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Keeps the crontab of the current user in line with the jobs an application declares.
 *
 * The command owns the lines of one scope ({@see CronLine}) and nothing else: comments,
 * variables and the jobs of other tools or of other scopes stay as they are. To add or change a
 * job, change its definition, then run `sync`: the crontab always follows what is declared.
 *
 * | Action               | Effect                                                                    |
 * | :------------------- | :------------------------------------------------------------------------ |
 * | `list`               | the jobs, their line in the crontab, and what `sync` would do to each     |
 * | `sync`               | shows the changes the crontab needs; **writes nothing**                   |
 * | `sync --apply`       | copies the crontab (with a backup directory), then writes the changes     |
 * | `enable <name>`      | uncomments the line of a job, until the next `sync` if its definition differs |
 * | `disable <name>`     | comments the line of a job out, until the next `sync` if its definition differs |
 * | `remove <name>`      | removes the line of a job; `sync` adds it back while it is still defined  |
 *
 * The writing actions share one lock, whatever the scope: two instances of an application on
 * the same machine share the crontab of their user, and two writes at once would lose one.
 *
 * Initialization (`$init`):
 *
 * - `scope` — the scope of the jobs, e.g. `myapp-staging` ;
 * - `jobs` — the jobs: {@see CronJob} objects, or their definitions (`name`, `schedule`, `command`,
 *   `enabled`, `log`) ;
 * - `backups` — the directory the crontab is copied to before each write ;
 * - `keep` — how many copies to keep (10 by default) ;
 * - `crontab` — the {@see Crontab} to work on (tests pass one with their own runner).
 *
 * A wrong scope or a wrong job does not stop the console from starting: the command says what
 * is wrong when it runs.
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class CronCommand extends Kernel
{
    /**
     * Creates the command.
     *
     * @param string|null $name      The name of the command.
     * @param Container   $container The dependency injection container.
     * @param array<string, mixed> $init The initialization parameters, see {@see CronParam}.
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     */
    public function __construct( ?string $name , Container $container , array $init = [] )
    {
        $backups = $init[ CronParam::BACKUPS ] ?? null ;
        $crontab = $init[ CronParam::CRONTAB ] ?? null ;
        $jobs    = $init[ CronParam::JOBS    ] ?? null ;
        $keep    = $init[ CronParam::KEEP    ] ?? null ;
        $scope   = $init[ CronParam::SCOPE   ] ?? null ;

        $this->backups = is_string( $backups ) && $backups !== '' ? $backups : null ;
        $this->crontab = $crontab instanceof Crontab ? $crontab : new Crontab() ;
        $this->jobs    = is_array( $jobs ) ? array_values( $jobs ) : [] ;
        $this->keep    = is_int( $keep ) ? $keep : $this->keep ;
        $this->scope   = is_string( $scope ) ? $scope : '' ;

        parent::__construct( $name , $container , $init ) ;
    }

    use LockCommandTrait ;

    /**
     * The option of `sync` that writes the crontab.
     */
    public const string APPLY = 'apply' ;

    /**
     * The name of the lock the writing actions share.
     */
    public const string LOCK = 'oihana-cron' ;

    /**
     * The argument naming the job of `enable`, `disable` and `remove`.
     */
    public const string NAME = 'name' ;

    /**
     * The directory the crontab is copied to before each write, or null to copy nothing.
     *
     * @var string|null
     */
    public ?string $backups = null ;

    /**
     * The crontab the command works on.
     *
     * @var Crontab
     */
    public Crontab $crontab ;

    /**
     * The jobs of the scope, as given: {@see CronJob} objects or their definitions.
     *
     * @var list<mixed>
     */
    public array $jobs = [] ;

    /**
     * How many copies of the crontab to keep.
     *
     * @var int
     */
    public int $keep = 10 ;

    /**
     * The scope of the jobs.
     *
     * @var string
     */
    public string $scope = '' ;

    /**
     * Declares the arguments and the options of the command.
     *
     * @return void
     */
    protected function configure() :void
    {
        $this
            ->addArgument
            (
                CommandArg::ACTION ,
                InputArgument::OPTIONAL ,
                sprintf( 'The action: %s.' , implode( ', ' , $this->actionNames() ) ) ,
                CronAction::LIST
            )
            ->addArgument
            (
                self::NAME ,
                InputArgument::OPTIONAL ,
                'The job of enable, disable and remove.'
            )
            ->addOption
            (
                self::APPLY ,
                null ,
                InputOption::VALUE_NONE ,
                'sync: write the crontab, instead of only showing the changes.'
            ) ;
    }

    /**
     * Runs the action.
     *
     * @param InputInterface  $input  The input.
     * @param OutputInterface $output The output.
     *
     * @return int `0` when the action succeeded; `1` when it failed; `2` for a wrong action or a missing name.
     *
     * @throws ReflectionException
     */
    protected function execute( InputInterface $input , OutputInterface $output ) :int
    {
        $action       = $input->getArgument( CommandArg::ACTION ) ;
        $this->action = is_string( $action ) ? $action : '' ;

        [ $io , $timestamp ] = $this->startCommand( $input , $output ) ;

        if( !in_array( $this->action , $this->actionNames() , true ) )
        {
            $io->error( sprintf( 'Unknown action "%s". Allowed: %s.' , $this->action , implode( ', ' , $this->actionNames() ) ) ) ;
            return ExitCode::INVALID ;
        }

        $name = $input->getArgument( self::NAME ) ;
        $name = is_string( $name ) ? $name : '' ;

        if( $this->action !== CronAction::LIST && $this->action !== CronAction::SYNC && $name === '' )
        {
            $io->error( sprintf( 'The action "%s" needs the name of a job.' , $this->action ) ) ;
            return ExitCode::INVALID ;
        }

        $writes = $this->action !== CronAction::LIST && ( $this->action !== CronAction::SYNC || $input->getOption( self::APPLY ) ) ;

        if( $writes && !$this->acquireLock( $input , $output , self::LOCK ) )
        {
            return ExitCode::FAILURE ;
        }

        try
        {
            $io->text( sprintf( 'Scope: <info>%s</info>' , $this->scope ) ) ;
            $io->newLine() ;

            $status = match( $this->action )
            {
                CronAction::SYNC    => $this->sync   ( $io , (bool) $input->getOption( self::APPLY ) ) ,
                CronAction::ENABLE  => $this->toggle ( $io , $name , true  ) ,
                CronAction::DISABLE => $this->toggle ( $io , $name , false ) ,
                CronAction::REMOVE  => $this->remove ( $io , $name ) ,
                default             => $this->list   ( $io ) ,
            } ;
        }
        catch( InvalidArgumentException | RuntimeException $exception )
        {
            $io->error( $exception->getMessage() ) ;
            $status = ExitCode::FAILURE ;
        }
        finally
        {
            if( $writes )
            {
                $this->unlock() ;
            }
        }

        return $this->endCommand( $input , $output , $status , $timestamp ) ;
    }

    /**
     * Shows the jobs, their line in the crontab, and what `sync` would do.
     *
     * @param SymfonyStyle $io The output.
     *
     * @return int `0`.
     */
    protected function list( SymfonyStyle $io ) :int
    {
        $plan = $this->plan() ;
        $rows = [] ;

        foreach( $plan->jobs as $name => $job )
        {
            $installed = $plan->installed[ $name ] ?? null ;

            $rows[] =
            [
                $name ,
                $job->schedule ,
                $job->enabled ? 'enabled' : 'disabled' ,
                $installed === null ? 'missing' : ( CronLine::isDisabled( $installed ) ? 'disabled' : 'enabled' ) ,
                $this->describe( $plan->change( $name ) ) ,
            ] ;
        }

        foreach( $plan->changes as $change )
        {
            if( $change->type === CronChange::REMOVE )
            {
                $rows[] =
                [
                    $change->name ,
                    '' ,
                    'not declared' ,
                    CronLine::isDisabled( (string) $change->before ) ? 'disabled' : 'enabled' ,
                    $this->describe( $change ) ,
                ] ;
            }
        }

        if( $rows === [] )
        {
            $io->text( 'No job is declared, and the crontab holds no line of this scope.' ) ;
            return ExitCode::SUCCESS ;
        }

        $io->table( [ 'Job' , 'Schedule' , 'Declared' , 'Crontab' , 'Sync' ] , $rows ) ;

        return ExitCode::SUCCESS ;
    }

    /**
     * Removes the lines of a job.
     *
     * @param SymfonyStyle $io   The output.
     * @param string       $name The name of the job.
     *
     * @return int `0` when a line was removed, `1` when the crontab holds none.
     */
    protected function remove( SymfonyStyle $io , string $name ) :int
    {
        $lines = $this->crontab->read() ;
        $next  = array_values( array_filter( $lines , fn( string $line ) :bool => CronLine::name( $line , $this->scope ) !== $name ) ) ;

        if( count( $next ) === count( $lines ) )
        {
            $io->error( sprintf( 'The crontab holds no line of the job "%s".' , $name ) ) ;
            return ExitCode::FAILURE ;
        }

        $this->save( $io , $lines , $next ) ;

        $io->success( sprintf( 'The job "%s" was removed from the crontab.' , $name ) ) ;

        if( isset( $this->plan( $next )->jobs[ $name ] ) )
        {
            $io->note( sprintf( 'The job "%s" is still declared: the next sync adds it back.' , $name ) ) ;
        }

        return ExitCode::SUCCESS ;
    }

    /**
     * Shows the changes the crontab needs, and writes them when asked to.
     *
     * @param SymfonyStyle $io    The output.
     * @param bool         $apply True to write the crontab.
     *
     * @return int `0`.
     */
    protected function sync( SymfonyStyle $io , bool $apply ) :int
    {
        $lines = $this->crontab->read() ;
        $plan  = $this->plan( $lines ) ;

        if( $plan->isEmpty() )
        {
            $io->success( 'The crontab is up to date.' ) ;
            return ExitCode::SUCCESS ;
        }

        foreach( $plan->changes as $change )
        {
            $io->text( sprintf( '<comment>%s</comment> %s' , $change->type , $change->name ) ) ;

            if( $change->before !== null )
            {
                $io->text( '  - ' . $change->before ) ;
            }

            if( $change->after !== null )
            {
                $io->text( '  + ' . $change->after ) ;
            }
        }

        $io->newLine() ;

        if( !$apply )
        {
            $io->note( sprintf( '%d change(s). Nothing was written: run again with --%s to write them.' , count( $plan->changes ) , self::APPLY ) ) ;
            return ExitCode::SUCCESS ;
        }

        $this->save( $io , $lines , $plan->lines ) ;

        $io->success( sprintf( '%d change(s) written in the crontab.' , count( $plan->changes ) ) ) ;

        return ExitCode::SUCCESS ;
    }

    /**
     * Comments the lines of a job out, or uncomments them.
     *
     * @param SymfonyStyle $io     The output.
     * @param string       $name   The name of the job.
     * @param bool         $enable True to uncomment, false to comment out.
     *
     * @return int `0` when the line is as asked, `1` when the crontab holds no line of the job.
     */
    protected function toggle( SymfonyStyle $io , string $name , bool $enable ) :int
    {
        $lines = $this->crontab->read() ;
        $next  = [] ;
        $found = false ;

        foreach( $lines as $line )
        {
            if( CronLine::name( $line , $this->scope ) === $name )
            {
                $found = true ;
                $line  = $enable ? CronLine::enable( $line ) : CronLine::disable( $line ) ;
            }

            $next[] = $line ;
        }

        $state = $enable ? 'enabled' : 'disabled' ;

        if( !$found )
        {
            $io->error( sprintf( 'The crontab holds no line of the job "%s": run sync first.' , $name ) ) ;
            return ExitCode::FAILURE ;
        }

        if( $next === $lines )
        {
            $io->success( sprintf( 'The job "%s" is already %s.' , $name , $state ) ) ;
            return ExitCode::SUCCESS ;
        }

        $this->save( $io , $lines , $next ) ;

        $io->success( sprintf( 'The job "%s" is %s.' , $name , $state ) ) ;

        $job = $this->plan( $next )->jobs[ $name ] ?? null ;

        if( $job !== null && $job->enabled !== $enable )
        {
            $io->note( sprintf
            (
                'The job "%s" is declared %s: the next sync puts it back. Change its declaration to keep it %s.' ,
                $name ,
                $job->enabled ? 'enabled' : 'disabled' ,
                $state
            )) ;
        }

        return ExitCode::SUCCESS ;
    }

    /**
     * The names of the actions.
     *
     * @return list<string> The actions, in alphabetical order.
     */
    private function actionNames() :array
    {
        return array_values( array_filter( CronAction::enums() , is_string( ... ) ) ) ;
    }

    /**
     * What `sync` would do to a job, in words.
     *
     * @param CronChange|null $change The change of the job, or null.
     *
     * @return string The description.
     */
    private function describe( ?CronChange $change ) :string
    {
        return match( $change?->type )
        {
            CronChange::ADD     => 'adds it' ,
            CronChange::DISABLE => 'disables it' ,
            CronChange::ENABLE  => 'enables it' ,
            CronChange::REMOVE  => 'removes it' ,
            CronChange::UPDATE  => 'updates it' ,
            default             => 'up to date' ,
        } ;
    }

    /**
     * The plan of the jobs of the scope against lines of the crontab.
     *
     * @param list<string>|null $lines The lines, or null to read the crontab.
     *
     * @return CrontabPlan The plan.
     *
     * @throws InvalidArgumentException When the scope or a job is not valid.
     */
    private function plan( ?array $lines = null ) :CrontabPlan
    {
        $jobs = [] ;

        foreach( $this->jobs as $job )
        {
            $jobs[] = $job instanceof CronJob ? $job : CronJob::fromArray( is_array( $job ) ? $job : [] ) ;
        }

        return new CrontabPlan( $this->scope , $jobs , $lines ?? $this->crontab->read() ) ;
    }

    /**
     * Copies the crontab when a backup directory is set, then writes the new lines.
     *
     * @param SymfonyStyle $io      The output.
     * @param list<string> $current The lines of the crontab now.
     * @param list<string> $next    The lines to write.
     *
     * @return void
     */
    private function save( SymfonyStyle $io , array $current , array $next ) :void
    {
        if( $this->backups !== null && $this->backups !== '' )
        {
            $io->text( sprintf( 'Backup: <info>%s</info>' , $this->crontab->backup( $current , $this->backups , $this->keep ) ) ) ;
        }
        else
        {
            $io->text( 'No backup directory is set: the crontab is written without a copy.' ) ;
        }

        $this->crontab->write( $next ) ;
    }
}
