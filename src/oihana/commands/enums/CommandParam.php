<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the common command's parameters.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class CommandParam
{
    use ConstantsTrait ;

    /**
     * The 'batchSize' parameter.
     */
    public const string BATCH_SIZE = 'batchSize' ;

    /**
     * The 'clearable' parameter.
     */
    public const string CLEARABLE = 'clearable' ;

    /**
     * The 'actions' parameter.
     */
    public const string ACTIONS = 'actions' ;

    /**
     * The 'command' parameter.
     */
    public const string COMMAND = 'command' ;

    /**
     * The 'chown' parameter.
     */
    public const string CHOWN = 'chown' ;

    /**
     * The 'config' parameter.
     */
    public const string CONFIG = 'config' ;

    /**
     * The 'configPath' parameter.
     */
    public const string CONFIG_PATH = 'configPath' ;

    /**
     * The 'database' parameter.
     */
    public const string DATABASE = 'database' ;

    /**
     * The 'description' parameter.
     */
    public const string DESCRIPTION = 'description' ;

    /**
     * The 'help' parameter.
     */
    public const string HELP = 'help' ;

    /**
     * The 'id' parameter.
     */
    public const string ID = 'id' ;

    /**
     * The 'inflector' parameter.
     */
    public const string INFLECTOR = 'inflector' ;

    /**
     * The 'jsonOptions' parameter.
     */
    public const string JSON_OPTIONS = 'jsonOptions' ;

    /**
     * The 'mysqlRoot' parameter.
     */
    public const string MYSQL_ROOT = 'mysqlRoot' ;

    /**
     * The 'server' parameter.
     */
    public const string SERVER = 'server' ;
}