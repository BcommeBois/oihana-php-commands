<?php

namespace oihana\commands\traits;

use oihana\enums\Char;
use oihana\enums\Param;
use oihana\commands\exceptions\MissingPassphraseException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait to manage a passphrase for console commands.
 *
 * Provides methods to handle an internal passphrase value, including
 * prompting the user interactively if the passphrase is not provided
 * and optionally throwing an exception if the passphrase is missing.
 *
 * This trait requires the {@see IOTrait} to provide SymfonyStyle I/O helpers.
 *
 * ## Usage Example
 * ```php
 * use oihana\commands\traits\PassphraseTrait;
 * use Symfony\Component\Console\Command\Command;
 * use Symfony\Component\Console\Input\InputInterface;
 * use Symfony\Component\Console\Output\OutputInterface;
 *
 * class MyCommand extends Command
 * {
 *     use PassphraseTrait;
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         try
 *         {
 *             $passphrase = $this->getPassPhrase($input, $output) ;
 *         }
 *         catch ( MissingPassphraseException $e )
 *         {
 *             $output->writeln('<error>' . $e->getMessage() . '</error>');
 *             return 1 ;
 *         }
 *
 *         // Use $passphrase for secured operations...
 *
 *         return 0;
 *     }
 * }
 * ```
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz
 * @since   1.0.0
 */
trait PassphraseTrait
{
    use IOTrait ;

    /**
     * The internal passphrase value.
     * @var ?string
     */
    protected ?string $passphrase = null ;

    /**
     * Returns the passphrase. if the command is interactive a question is asked to the command user to prompt the passphrase.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $throwable Indicates if the method throws a MissingPassphraseException if the passphrase is null.
     * @return string|null Returns the passphrase.
     * @throws MissingPassphraseException
     */
    protected function getPassPhrase( InputInterface $input , OutputInterface $output , bool $throwable = true ):?string
    {
        $passphrase = $input->getOption( Param::PASS_PHRASE ) ?? $this->passphrase ;

        if( $input->isInteractive() && ( !isset( $passphrase ) || $passphrase == Char::EMPTY ) )
        {
            $io = $this->getIO( $input , $output ) ;
            $io->newLine() ;
            $passphrase = $io->askHidden( 'Please enter your passphrase' ) ;
            $again      = $io->askHidden( 'Please enter your passphrase again for verification' ) ;
            assert(  $again == $passphrase  , 'The passphrase must be the same.' ) ;
        }

        if( $throwable && ( !isset( $passphrase ) || $passphrase == Char::EMPTY  ) )
        {
            throw new MissingPassphraseException( 'The passphrase is required.' ) ;
        }

        return $passphrase ;
    }

    /**
     * Initialize the internal passphrase value.
     * @param array $init
     * @return void
     */
    public function initializePassphrase( array $init = [] ):void
    {
        $this->passphrase = $init[ Param::PASS_PHRASE ] ?? $this->passphrase ;
    }
}