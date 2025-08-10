<?php

namespace oihana\commands\options;

use oihana\enums\Char;
use ReflectionException;

use oihana\options\Options;

use function oihana\files\isMac;

/**
 * Represents the available options for the Unix `chown` command.
 *
 * This class provides a typed configuration object for building command-line arguments
 * used with the `chown` command, such as recursive mode, verbose output,
 * dereferencing symbolic links, and ownership/group specifications.
 *
 * Example:
 * ```php
 * $options = new ChownOptions
 * ([
 *     'recursive' => true ,
 *     'verbose'   => true ,
 *     'group'     => 'www-data' ,
 *     'owner'     => 'www-data' ,
 *     'path'      => '/var/www/html' ,
 * ]);
 * echo (string) $options;
 * // --recursive --verbose
 * ```
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ChownOptions extends Options
{
    /**
     * Restrict the operation to files owned by the specified owner:group.
     * Example: "root:root"
     *
     * Corresponds to: `--from=USER:GROUP`
     *
     * @var string|null
     */
    public ?string $from = null ;

    /**
     * The group to assign to the file or directory.
     * @var string|null
     */
    public ?string $group = null ;

    /**
     * If true, do not dereference symbolic links when traversing directories.
     *
     * Corresponds to: `--no-dereference`
     *
     * @var bool|null
     */
    public ?bool $noDereference = null ;

    /**
     * The new owner (user) of the file or directory.
     * @var string|null
     */
    public ?string $owner = null  ;

    /**
     * The path to the file or directory to apply the ownership changes to.
     * This is usually the target of the `chown` command.
     * @var string|null
     */
    public ?string $path = null ;

    /**
     * Use the owner and group of the given reference file instead of specifying manually.
     *
     * Corresponds to: `--reference=FILE`
     *
     * @var string|null
     */
    public ?string $reference = null  ;

    /**
     * Whether to operate recursively on directories and their contents.
     *
     * Corresponds to: `--recursive` or `-R`
     *
     * @var bool|null
     */
    public ?bool $recursive = null ;

    /**
     * Indicates if the chown command must be executed with the sudo prefix.
     * @var bool|null
     */
    public ?bool $sudo = null ;

    /**
     * Whether to output the names of processed files/directories.
     *
     * Corresponds to: `--verbose` or `-v`
     *
     * @var bool|null
     */
    public ?bool $verbose = null ;

    /**
     * Converts this option set into a string representation of command-line arguments.
     *
     * @return string A formatted string suitable for CLI usage.
     * @throws ReflectionException If reflection fails during option parsing.
     */
    public function __toString() : string
    {
        return $this->getOptions
        (
            clazz     : ChownOption::class ,
            prefix    : isMac() ? Char::HYPHEN : Char::DOUBLE_HYPHEN ,
            excludes  : [ ChownOption::GROUP , ChownOption::PATH ,  ChownOption::OWNER , ChownOption::SUDO ] ,
            separator : Char::EQUAL
        ) ;
    }
}