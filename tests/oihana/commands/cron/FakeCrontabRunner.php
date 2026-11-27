<?php

declare(strict_types=1);

namespace tests\oihana\commands\cron;

use Closure;

use oihana\commands\cron\Crontab;
use oihana\commands\Process;

/**
 * Plays the `crontab` program in memory, so that no test ever reads or writes a real crontab.
 *
 * `crontab -l` gives the text held, or the "no crontab" error when there is none; `crontab <file>`
 * keeps the text of the file. Each call is recorded, and a status can be forced on reads or writes.
 */
final class FakeCrontabRunner
{
    /**
     * @param string|null $text The text of the crontab, or null for a user without a crontab.
     */
    public function __construct( public ?string $text = null )
    {
    }

    /**
     * @var list<list<string>>
     */
    public array $calls = [] ;

    /**
     * The error output and status `crontab -l` fails with, when set.
     *
     * @var array{string, int}|null
     */
    public ?array $readFailure = null ;

    /**
     * The files given to `crontab <file>`.
     *
     * @var list<string>
     */
    public array $writtenFiles = [] ;

    /**
     * The error output and status `crontab <file>` fails with, when set.
     *
     * @var array{string, int}|null
     */
    public ?array $writeFailure = null ;

    /**
     * The number of times the crontab was written.
     */
    public int $writes = 0 ;

    public function crontab() :Crontab
    {
        return new Crontab( $this->runner() ) ;
    }

    public function runner() :Closure
    {
        return function( array $command ) :Process
        {
            $this->calls[] = $command ;

            if( $command === [ Crontab::PROGRAM , '-l' ] )
            {
                if( $this->readFailure !== null )
                {
                    return new Process( [ Process::ERROR => $this->readFailure[ 0 ] , Process::STATUS => $this->readFailure[ 1 ] , Process::OUTPUT => '' ] ) ;
                }

                if( $this->text === null )
                {
                    return new Process( [ Process::ERROR => "no crontab for user\n" , Process::STATUS => 1 , Process::OUTPUT => '' ] ) ;
                }

                return new Process( [ Process::OUTPUT => $this->text , Process::ERROR => '' , Process::STATUS => 0 ] ) ;
            }

            $file = $command[ 1 ] ;

            $this->writtenFiles[] = $file ;

            if( $this->writeFailure !== null )
            {
                return new Process( [ Process::ERROR => $this->writeFailure[ 0 ] , Process::STATUS => $this->writeFailure[ 1 ] , Process::OUTPUT => '' ] ) ;
            }

            $this->text = (string) file_get_contents( $file ) ;
            $this->writes++ ;

            return new Process( [ Process::OUTPUT => '' , Process::ERROR => '' , Process::STATUS => 0 ] ) ;
        } ;
    }
}
