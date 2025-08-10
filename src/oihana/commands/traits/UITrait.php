<?php

namespace oihana\commands\traits;

use Generator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides utilities for building interactive UI elements like progress bars in Symfony Console-based applications.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait UITrait
{
    use IOTrait ;

    /**
     * Creates and configures a Symfony Console ProgressBar.
     *
     * @param OutputInterface $output             The output interface (usually from a command).
     * @param int             $max                The maximum number of steps (0 for indeterminate).
     * @param string          $format             The format name to use (e.g., 'pretty').
     * @param int             $barWidth           The width of the progress bar.
     * @param array|null      $formatDefinitions  Custom format definitions [name => format string].
     *
     * @return ProgressBar The initialized progress bar instance.
     */
    public function createProgressBar
    (
        OutputInterface $output ,
        int             $max                = 0 ,
        string          $format            = 'pretty' ,
        int             $barWidth          = 50 ,
        ?array          $formatDefinitions = null
    ):ProgressBar
    {
        $progressBar = new ProgressBar( $output , $max ) ;
        $this->initializeProgressBar( $progressBar , $format , $barWidth , $formatDefinitions ) ;
        return $progressBar ;
    }

    /**
     * Creates a progress bar using a SymfonyStyle instance.
     *
     * @param SymfonyStyle $io                 The SymfonyStyle instance.
     * @param int          $max                The maximum number of steps.
     * @param string       $format             The format name to use.
     * @param int          $barWidth           The width of the progress bar.
     * @param array|null   $formatDefinitions  Custom format definitions [name => format string].
     *
     * @return ProgressBar The initialized progress bar instance.
     */
    public function createProgressBarFromIO
    (
        SymfonyStyle $io ,
        int          $max               = 0 ,
        string       $format            = 'pretty' ,
        int          $barWidth          = 50 ,
        ?array       $formatDefinitions = null
    )
    :ProgressBar
    {
        $progressBar = $io->createProgressBar( $max );
        $this->initializeProgressBar( $progressBar , $format , $barWidth , $formatDefinitions ) ;
        return $progressBar ;
    }

    /**
     * Applies configuration to a ProgressBar instance.
     *
     * @param ProgressBar $progressBar         The progress bar to initialize.
     * @param string      $format              The format name to use (e.g., 'pretty').
     * @param int         $barWidth            The width of the progress bar.
     * @param array|null  $formatDefinitions   Optional array of custom format definitions.
     *
     * @return static The current trait instance (for method chaining).
     */
    public function initializeProgressBar
    (
        ProgressBar $progressBar ,
        string      $format            = 'pretty' ,
        int         $barWidth          = 50 ,
        ?array      $formatDefinitions = null
    )
    :static
    {
        if( is_array( $formatDefinitions ) )
        {
            foreach( $formatDefinitions as $name => $format )
            {
                $progressBar->setFormatDefinition( $name , $format );
            }
        }
        else
        {
            $progressBar->setFormatDefinition( $format , "[%bar%]" . PHP_EOL . PHP_EOL . " %message% | %percent:3s%% (%current%/%max%) | %elapsed%" . PHP_EOL ) ;
        }

        $progressBar->setFormat( $format );
        $progressBar->setBarWidth( $barWidth );
        $progressBar->setRedrawFrequency(1 );

        return $this ;
    }

    /**
     * Iterate over an iterable collection with a progress bar and optional per-item messages.
     *
     * Example usage:
     * ```php
     * $iterator = $this->progressBarIterator( $progressBar , $items , fn($item) => "Processing {$item->name}") ;
     * foreach ( $iterator as $item)
     * {
     *     // Process $item here
     * }
     * ```
     *
     * @template T
     * @param ?ProgressBar                     $progressBar  The progress bar reference.
     * @param iterable<T>                      $items        Iterable collection of items.
     * @param (callable(T):string)|string|null $message Optional callback to generate message per item: fn(T): string.
     * @param string|null                      $start        The message when the iteration is starting.
     * @param string|null                      $finish       The message when the iteration is ending.
     *
     * @return Generator<T>  Generator yielding each item.
     */
    public function progressBarIterator
    (
       ?ProgressBar          $progressBar,
        iterable             $items ,
        string|callable|null $message = null,
        ?string              $start   = null,
        ?string              $finish  = null,
    ): Generator
    {
        $progressBar?->start() ;

        if( $start !== null && $start !== '' )
        {
            $progressBar?->setMessage( $start ) ;
        }

        foreach ( $items as $item )
        {
            if ( $progressBar !== null && $message !== null )
            {
                $progressBar->setMessage(  is_callable( $message ) ? $message( $item ) : $message );
            }

            $progressBar?->advance() ;

            yield $item ;
        }

        if( $finish !== null && $finish !== '' )
        {
            $progressBar?->setMessage( $finish ) ;
        }

        $progressBar?->finish() ;
    }
}