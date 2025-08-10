<?php

namespace oihana\commands\traits;

use oihana\enums\Char;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Provides functionality to initialize and store a Symfony String Inflector.
 *
 * The inflector is used to convert words between singular and plural forms.
 * This trait offers a flexible initialization mechanism allowing:
 * - Direct assignment of an {@see InflectorInterface} instance
 * - Resolution from a PSR-11 container via a service ID
 * - Automatic fallback to {@see EnglishInflector} if none is provided
 *
 * Intended for classes that need consistent singular/plural transformations
 * with support for dependency injection and configuration.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait InflectorTrait
{
    /**
     * The string inflector reference.
     * @var InflectorInterface
     */
    public InflectorInterface $inflector ;

    /**
     * The 'inflector' parameter.
     */
    public const string INFLECTOR = 'inflector' ;

    /**
     * Initializes the string inflector to be used by this instance.
     *
     * This method determines which {@see InflectorInterface} implementation should be applied:
     * - If `$init[static::INFLECTOR]` is a non-empty string corresponding to a service name
     *   available in the DI container, that service will be fetched and used as the inflector.
     * - If `$init[static::INFLECTOR]` is already an {@see InflectorInterface} instance, it is used directly.
     * - Otherwise, the provided `$defaultInflector` is used; if not given, a new {@see EnglishInflector} is created.
     *
     * @param array<string,mixed>       $init              Optional initialization parameters.
     *                                                     May contain the key `static::INFLECTOR` with:
     *                                                        - A service ID (string) resolvable via `$container`
     *                                                        - Or an {@see InflectorInterface} instance.
     * @param ContainerInterface|null   $container         Optional PSR-11 container for resolving a service name into an {@see InflectorInterface}.
     * @param InflectorInterface|null   $defaultInflector  Fallback inflector to use if no specific instance is found or provided.
     *
     * @return static  Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving the inflector service.
     * @throws NotFoundExceptionInterface  If the requested inflector service is not found in the container.
     */
    protected function initializeInflector
    (
        array               $init             = [] ,
        ?ContainerInterface $container        = null ,
        ?InflectorInterface $defaultInflector = null
    )
    :static
    {
        $inflector = $init[ static::INFLECTOR ] ?? null ;

        if( is_string( $inflector ) && $inflector != Char::EMPTY && $container?->has( $inflector ) )
        {
            $inflector = $container->get( $inflector ) ;
        }

        $this->inflector
            = $inflector instanceof InflectorInterface
            ? $inflector
            : ( $defaultInflector ?? new EnglishInflector() ) ;

        return $this;
    }
}