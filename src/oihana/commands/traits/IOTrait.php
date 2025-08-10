<?php

namespace oihana\commands\traits;

use oihana\enums\Char;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function oihana\core\strings\padStart;

/**
 * Represents a terminal command within the Symfony application.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait IOTrait
{
    /**
     * The command writer.
     * @var ?SymfonyStyle
     */
    public ?SymfonyStyle $io = null ;

    /**
     * Executes a batch of IO-aware callback actions using `runIOAction()`.
     *
     * This method takes an array of associative arrays (actions), each containing:
     * - `callback` (callable, required): the function to execute.
     * - `title` (string, optional): a section title to print before the callback.
     * - `finish` (string, optional): a message to print after the callback completes.
     * - `endLines` (int, optional): number of newlines to append at the end (default: 2).
     *
     * The provided `$io` instance is passed to all actions for styled output.
     * If no `callback` is provided in an action, that entry is skipped.
     *
     * @param array $actions An array of actions to run, each as an associative array with keys:
     *                       - `callback` (callable): required
     *                       - `title` (string): optional
     *                       - `finish` (string): optional
     *                       - `endLines` (int): optional, defaults to 2
     * @param SymfonyStyle|null $io Optional SymfonyStyle instance for styled output.
     * @param bool $numbering
     * @return void
     *
     * @example
     * ```php
     * $this->batchIOActions
     * ([
     *     [
     *         'callback'  => fn(SymfonyStyle $io) => $this->initializeDb($io),
     *         'title'     => 'Step 1: Database Initialization',
     *         'finish'    => '✅ Done.',
     *         'endLines'  => 1,
     *     ],
     *     [
     *         'callback'  => fn(SymfonyStyle $io) => $this->migrateData($io),
     *         'title'     => 'Step 2: Data Migration',
     *     ],
     * ]);
     * ```
     */
    public function batchIOActions( array $actions = [] , ?SymfonyStyle $io = null , bool $numbering = false ):void
    {
        if( $numbering )
        {
            $counter = 1;
            $length  = count( $actions ) ;
            for ( $i = 0 ; $i < $length ; $i++)
            {
                $title = $actions[$i]['title'] ?? null;

                if( $title === null )
                {
                    continue;
                }

                $prefix = padStart( (string) $counter , 2 , '0' );

                $actions[$i]['title'] = $title !== Char::EMPTY ? ( $prefix . '. ' . $title ) : $prefix;

                $counter++;
            }
        }

        foreach( $actions as $action )
        {
            $callback = $action[ 'callback' ] ?? null ;
            if( $callback !== null )
            {
                $this->runIOAction
                (
                    callback : $callback                    ,
                    io       : $io                          ,
                    title    : $action[ 'title'    ] ?? ''  ,
                    finish   : $action[ 'finish'   ] ?? ''  ,
                    endLines : $action[ 'endLines' ] ?? 2   ,
                ) ;
            }
        }
    }

    /**
     * Returns the IO writer reference.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return SymfonyStyle
     */
    public function getIO( InputInterface $input , OutputInterface $output ):SymfonyStyle
    {
        if( $this->io === null )
        {
            $this->io = new SymfonyStyle( $input , $output ) ;
        }
        return $this->io ;
    }

    /**
     * Executes a callback within a styled Symfony Console context (IO).
     *
     * This method ensures consistent output formatting by wrapping the given callback in a SymfonyStyle context.
     * If both `$input` and `$output` are provided, it automatically
     * creates or retrieves a SymfonyStyle instance using either the internal `getIO()` method
     * or a custom `$getIO` callable. It optionally renders a section title before execution,
     * a closing message after execution, and appends a number of newlines at the end.
     *
     * If `$input` or `$output` are null, the method simply invokes the callback without styling.
     *
     * @param callable             $callback  The main logic to execute. If IO is available, the callable should accept a SymfonyStyle argument.
     * @param InputInterface|null  $input     Optional Symfony Console input interface.
     * @param OutputInterface|null $output    Optional Symfony Console output interface.
     * @param string               $title     Optional title printed as a section header before executing the callback.
     * @param string               $finish    Optional closing message printed after executing the callback.
     * @param int                  $endLines  Number of new lines to append after the action (default: 2).
     * @param callable|null        $getIO     Optional callable to retrieve a custom SymfonyStyle instance (receives $input, $output).
     *
     * @return mixed The return value from the callback.
     *
     * @example
     * ```php
     * $this->runAction
     * (
     *     callback : fn( ?SymfonyStyle $io ) => $this->wordPressInitializeDatabase( verbose:$verbose ) ,
     *     input    : $input ,
     *     output   : $output ,
     *     title    : '01. Initialize the Mysql Database' ,
     *     finish   : '🗄️ The WordPress database is ready.'
     * );
     * ```
     */
    public function runAction
    (
        callable         $callback  ,
        ?InputInterface  $input    = null ,
        ?OutputInterface $output   = null ,
        string           $title     = '' ,
        string           $finish    = '',
        int              $endLines  = 1 ,
        callable|null    $getIO     = null
    )
    :mixed
    {
        if( $input === null || $output === null )
        {
            return $callback() ;
        }

        $io = $getIO ? $getIO( $input, $output ) : $this->getIO( $input , $output ) ;

        return $this->runIOAction( $callback , $io , $title , $finish , $endLines ) ;
    }

    /**
     * Executes a callback within a styled Symfony Console context (IO).
     *
     * This method runs the given callback, passing the provided `SymfonyStyle` instance
     * to it for consistent console output formatting. It optionally displays a section title
     * before executing the callback and a finishing message afterward.
     * Finally, it appends a specified number of new lines.
     *
     * If no title is given, no section header is printed.
     * If no finish message is given, no closing message is printed.
     *
     * @param callable      $callback  The callable to execute. Receives the `SymfonyStyle` instance as an argument.
     * @param ?SymfonyStyle $io        The `SymfonyStyle` instance used for console output.
     * @param string        $title     Optional section title displayed before running the callback.
     * @param string        $finish    Optional finishing message displayed after the callback completes.
     * @param int           $endLines  Number of new lines to append after the finishing message (default is 2).
     *
     * @return mixed The return value from the callback.
     *
     * @example
     * ```php
     * $this->runIOAction(
     *     callback: fn( ?SymfonyStyle $io) => $this->wordPressInitializeDatabase(verbose: $verbose),
     *     io: $io,
     *     title: '01. Initialize the Mysql Database',
     *     finish: '🗄️ The WordPress database is ready.'
     * );
     * ```
     */
    public function runIOAction( callable $callback , ?SymfonyStyle $io = null , string $title = '' , string $finish = '' , int $endLines = 2 ) :mixed
    {
        $hasEndLines = false ;

        if ( $io !== null &&  $title !== '' )
        {
            $io->section( $title );
            $hasEndLines = true ;
        }

        $result = $callback( $io ) ;

        if ( $io !== null && $finish !== '' )
        {
            $io->newLine();
            $io->text( $finish );
            $hasEndLines = true ;
        }

        if( $hasEndLines )
        {
            $io?->newLine( $endLines ) ;
        }

        return $result ;
    }
}