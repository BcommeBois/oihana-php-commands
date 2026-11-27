<?php

namespace oihana\commands\cron;

use Closure;
use DateTimeImmutable;
use RuntimeException;

use oihana\commands\Process;

use function oihana\commands\helpers\runProcess;

/**
 * The crontab of the user running PHP: read it, save a copy of it, write it.
 *
 * It goes through the `crontab` program, never through the files of the cron daemon:
 *
 * - **read** runs `crontab -l`. A user without a crontab gets an empty list; any other failure
 *   raises — an unreadable crontab must never be taken for an empty one, and then overwritten ;
 * - **write** puts the lines in a temporary file and runs `crontab <file>`, which replaces the
 *   crontab at once and refuses a file with a syntax error. The temporary file is always removed ;
 * - **backup** copies lines into a dated file (`crontab-20260115-061500-123456.bak`, mode `0600`:
 *   a crontab may hold secrets), and keeps only the most recent ones.
 *
 * The program runs through {@see runProcess()}; tests pass their own runner instead.
 *
 * @example
 * ```php
 * use oihana\commands\cron\Crontab;
 *
 * $crontab = new Crontab() ;
 * $lines   = $crontab->read() ;
 *
 * $crontab->backup( $lines , '/srv/app/var/crontab' ) ;
 * $crontab->write( [ ...$lines , '0 6 * * * /srv/app/check.sh' ] ) ;
 * ```
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class Crontab
{
    /**
     * Creates the access to the crontab.
     *
     * @param Closure|null $runner  Runs a command given as a list of arguments and returns its {@see Process};
     *                              null runs it with {@see runProcess()}.
     * @param float|null   $timeout The time limit of the `crontab` program, in seconds.
     */
    public function __construct( ?Closure $runner = null , ?float $timeout = 30.0 )
    {
        $this->runner = $runner ?? static fn( array $command ) :Process => runProcess( $command , $timeout ) ;
    }

    /**
     * What `crontab -l` writes on its error output for a user without a crontab.
     */
    public const string NO_CRONTAB = 'no crontab for' ;

    /**
     * The name of the `crontab` program.
     */
    public const string PROGRAM = 'crontab' ;

    /**
     * Runs a command given as a list of arguments.
     *
     * @var Closure(list<string>): Process
     */
    protected Closure $runner ;

    /**
     * Saves lines in a new dated file of a directory, and removes the oldest files beyond `$keep`.
     *
     * @param list<string> $lines     The lines to save, as {@see Crontab::read()} gives them.
     * @param string       $directory The directory of the copies, created when missing.
     * @param int          $keep      How many copies to keep, at least one.
     *
     * @return string The path of the new copy.
     *
     * @throws RuntimeException When the directory or the copy cannot be written.
     */
    public function backup( array $lines , string $directory , int $keep = 10 ) :string
    {
        if( !is_dir( $directory ) && !@mkdir( $directory , 0700 , true ) && !is_dir( $directory ) )
        {
            throw new RuntimeException( sprintf( 'The crontab backup directory "%s" cannot be created.' , $directory ) ) ;
        }

        $path = sprintf
        (
            '%s/crontab-%s.bak' ,
            rtrim( $directory , '/' ) ,
            new DateTimeImmutable()->format( 'Ymd-His-u' )
        ) ;

        if( @file_put_contents( $path , self::text( $lines ) ) === false )
        {
            throw new RuntimeException( sprintf( 'The crontab backup "%s" cannot be written.' , $path ) ) ;
        }

        @chmod( $path , 0600 ) ;

        $copies = glob( rtrim( $directory , '/' ) . '/crontab-*.bak' ) ?: [] ;

        sort( $copies ) ;

        foreach( array_slice( $copies , 0 , max( 0 , count( $copies ) - max( 1 , $keep ) ) ) as $old )
        {
            @unlink( $old ) ;
        }

        return $path ;
    }

    /**
     * The lines of the crontab.
     *
     * @return list<string> The lines, without their line ends; an empty list for a user without a crontab.
     *
     * @throws RuntimeException When the crontab cannot be read.
     */
    public function read() :array
    {
        $process = $this->run( [ self::PROGRAM , '-l' ] ) ;

        if( $process->status !== 0 )
        {
            if( stripos( (string) $process->error , self::NO_CRONTAB ) !== false )
            {
                return [] ;
            }

            throw new RuntimeException( sprintf
            (
                'The crontab cannot be read (status %s): %s' ,
                $process->status ,
                trim( (string) $process->error )
            )) ;
        }

        $text = (string) $process->output ;

        if( $text === '' )
        {
            return [] ;
        }

        if( str_ends_with( $text , "\n" ) )
        {
            $text = substr( $text , 0 , -1 ) ;
        }

        return explode( "\n" , $text ) ;
    }

    /**
     * Replaces the crontab with the given lines.
     *
     * @param list<string> $lines The lines, without their line ends.
     *
     * @return void
     *
     * @throws RuntimeException When the temporary file cannot be written, or `crontab` refuses it.
     */
    public function write( array $lines ) :void
    {
        $file = $this->temporaryFile() ;

        if( $file === false )
        {
            throw new RuntimeException( 'The temporary file of the crontab cannot be created.' ) ;
        }

        try
        {
            if( @file_put_contents( $file , self::text( $lines ) ) === false )
            {
                throw new RuntimeException( sprintf( 'The temporary file "%s" of the crontab cannot be written.' , $file ) ) ;
            }

            $process = $this->run( [ self::PROGRAM , $file ] ) ;

            if( $process->status !== 0 )
            {
                throw new RuntimeException( sprintf
                (
                    'The crontab cannot be written (status %s): %s' ,
                    $process->status ,
                    trim( (string) $process->error )
                )) ;
            }
        }
        finally
        {
            @unlink( $file ) ;
        }
    }

    /**
     * Runs a command through the runner.
     *
     * @param list<string> $command The program and its arguments.
     *
     * @return Process What the command wrote, and its status.
     */
    protected function run( array $command ) :Process
    {
        return ( $this->runner )( $command ) ;
    }

    /**
     * Creates the temporary file the crontab is written to before `crontab` reads it.
     *
     * @return string|false The path of the file, or false when it cannot be created.
     */
    protected function temporaryFile() :string|false
    {
        return tempnam( sys_get_temp_dir() , 'crontab-' ) ;
    }

    /**
     * The text of a crontab: the lines, each ended by a line end — cron ignores a last line without one.
     *
     * @param list<string> $lines The lines.
     *
     * @return string The text; empty for no line.
     */
    private static function text( array $lines ) :string
    {
        return $lines === [] ? '' : implode( "\n" , $lines ) . "\n" ;
    }
}
