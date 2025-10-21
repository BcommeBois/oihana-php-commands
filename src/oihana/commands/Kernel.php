<?php

namespace oihana\commands;

use UnexpectedValueException;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use oihana\commands\enums\CommandParam;
use oihana\commands\traits\CommandTrait;
use oihana\commands\traits\ConsoleLoggerTrait;
use oihana\commands\traits\FileTrait;
use oihana\commands\traits\HelperTrait;
use oihana\commands\traits\InflectorTrait;
use oihana\commands\traits\LifecycleTrait;
use oihana\commands\traits\UITrait;
use oihana\date\traits\DateTrait;
use oihana\enums\Char;

use oihana\traits\ConfigTrait;
use oihana\traits\ContainerTrait;
use oihana\traits\IDTrait;
use oihana\traits\JsonOptionsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Command\Command;

/**
 * Kernel command class representing a Symfony Console command
 * integrated with dependency injection and extended with multiple traits.
 *
 * This class provides initialization logic for common command parameters
 * such as actions, batch size, description, and help text.
 *
 * It implements the PSR-3 LoggerInterface to provide logging capabilities.
 *
 * @package oihana\commands
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class Kernel extends Command implements LoggerInterface
{
    /**
     * Kernel constructor.
     *
     * Initializes the command with the given name and injects the DI container and parameters.
     *
     * @param string|null $name      The command name, or null to be set in configure().
     * @param Container   $container The dependency injection container.
     * @param array       $init      Initialization parameters for the command.
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct( ?string $name , Container $container , array $init = [] )
    {
        $this->initializeKernel( $container , $init ) ;
        parent::__construct( $name ) ;
    }

    use CommandTrait ,
        ConfigTrait ,
        ContainerTrait ,
        ConsoleLoggerTrait ,
        DateTrait , // TODO keep it ?
        FileTrait ,
        HelperTrait ,
        IDTrait ,
        InflectorTrait ,
        JsonOptionsTrait ,
        LifecycleTrait ,
        UITrait  ;

    /**
     * The current action of the command, or null if none is selected.
     *
     * @var string|null
     */
    public ?string $action = null ;

    /**
     * Optional white-list of possible actions that this command can perform.
     *
     * @var null|array<int, string>
     */
    public ?array $actions = null ;

    /**
     * Number of items to process per batch.
     *
     * @var int
     */
    public int $batchSize = 500 ;

    /**
     * Check if the given 'action' is register in the 'actions' white-list of the command.
     *
     * @param ?string $action The action to evaluates.
     *
     * @throws UnexpectedValueException If the given `action` is null, empty or not allowed and the `$actions` properties is not null.
     *
     * @return void
     */
    public function assertActions( ?string $action = null ):void
    {
        if( is_array( $this->actions ) && count( $this->actions ) > 0 )
        {
            if( !in_array( $this->action , $this->actions , true ) )
            {
                throw new UnexpectedValueException
                (
                    sprintf
                    (
                        'The action "%s" is not allowed. Allowed: %s' ,
                        $this->action ,
                        json_encode( $this->actions , JSON_UNESCAPED_SLASHES )
                    )
                );
            }
        }
    }

    /**
     * Initializes the list of actions allowed for this command.
     *
     * @param array $init Initialization parameters which may contain 'actions' key.
     *
     * @return static Returns the instance for method chaining.
     */
    public function initializeActions( array $init = [] ):static
    {
        $this->actions = $init[ CommandParam::ACTIONS ] ?? $this->actions ;
        return $this;
    }

    /**
     * Initializes the batch size parameter of the command.
     *
     * @param array $init Initialization parameters which may contain 'batchSize' key.
     *
     * @return static Returns the instance for method chaining.
     */
    public function initializeBatchSize( array $init = [] ) :static
    {
        $this->batchSize = $init[ CommandParam::BATCH_SIZE ] ?? $this->batchSize ;
        return $this ;
    }

    /**
     * Initializes the command description.
     *
     * If a non-empty description is provided in the initialization parameters,
     * it will be set for the command.
     *
     * @param array $init Initialization parameters which may contain 'description' key.
     *
     * @return static Returns the instance for method chaining.
     */
    public function initializeDescription( array $init = [] ):static
    {
        $description = $init[ CommandParam::DESCRIPTION ] ?? null ;
        if( $description !== null && $description != Char::EMPTY )
        {
            $this->setDescription( $description ) ;
        }
        return $this ;
    }

    /**
     * Initializes the help text of the command.
     *
     * If a non-empty help string is provided in the initialization parameters,
     * it will be set for the command.
     *
     * @param array $init Initialization parameters which may contain 'help' key.
     * @return static Returns the instance for method chaining.
     */
    public function initializeHelp( array $init = [] ):static
    {
        $help = $init[ CommandParam::HELP ] ?? null ;
        if( $help !== null && $help != Char::EMPTY )
        {
            $this->setHelp( $help ) ;
        }
        return $this ;
    }

    /**
     * Initializes the Kernel command with all parameters and dependencies.
     *
     * This method configures the container, command options, configuration,
     * description, help, IDs, inflectors, and JSON options.
     *
     * @param Container $container Dependency Injection container instance.
     * @param array     $init      Initialization parameters for the command.
     *
     * @return static Returns the instance for method chaining.
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     */
    protected function initializeKernel( Container $container , array $init = [] ) :static
    {
        $this->container = $container ;
        return $this
            ->initializeActions        ( $init )
            ->initializeBatchSize      ( $init )
            ->initializeCommandOptions ( $init )
            ->initializeConfig         ( $init , $container )
            ->initializeDescription    ( $init )
            ->initializeHelp           ( $init )
            ->initializeID             ( $init , $container )
            ->initializeInflector      ( $init , $container )
            ->initializeJsonOptions    ( $init )
            // ->initializeLogger         ( $init , $container , false )
            ;
    }
}