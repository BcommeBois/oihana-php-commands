<?php

namespace oihana\commands\traits;

use oihana\commands\styles\JsonStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a reusable helper for creating and accessing a {@see JsonStyle} instance.
 * This trait is intended to be used in Symfony Console commands or services that
 * need to output JSON data with syntax highlighting.
 *
 * It implements **lazy instantiation**: the {@see JsonStyle} instance is created
 * only when first requested via {@see self::getJson()} and then cached for reuse.
 *
 * Example usage:
 * ```php
 * use Symfony\Component\Console\Command\Command;
 * use Symfony\Component\Console\Input\InputInterface;
 * use Symfony\Component\Console\Output\OutputInterface;
 * use oihana\commands\traits\JsonStyleTrait;
 *
 * class ShowConfigCommand extends Command
 * {
 *     use JsonStyleTrait;
 *
 *     protected static $defaultName = 'app:config:show';
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         $data =
 *         [
 *             'version' => '1.0.6',
 *             'active'  => true,
 *             'tags'    => ['console', 'json', 'style']
 *        ];
 *
 *        // Writes JSON output with highlighting
 *        $this->getJson($output)->writeJson($data);
 *
 *        return Command::SUCCESS;
 *     }
 * }
 * ```
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
trait JsonStyleTrait
{
    /**
     * The cached {@see JsonStyle} instance.
     *
     * This property is initialized lazily when calling {@see self::getJson()}.
     * It allows reusing the same style object without creating multiple instances.
     *
     * @var ?JsonStyle
     */
    private ?JsonStyle $json = null ;

    /**
     * Returns the {@see JsonStyle} instance.
     *
     * If no instance has been created yet, this method will instantiate one,
     * passing the given {@see OutputInterface} to the constructor.
     *
     * @param OutputInterface $output The Symfony Console output implementation.
     *
     * @return JsonStyle The cached or newly created JSON style instance.
     *
     * @example
     * ```php
     * $style = $this->getJson($output);
     * $style->writeJson(['status' => 'ok']);
     * ```
     */
    public function getJson( OutputInterface $output ):JsonStyle
    {
        if( $this->json === null )
        {
            $this->json = new JsonStyle( $output ) ;
        }
        return $this->json ;
    }
}