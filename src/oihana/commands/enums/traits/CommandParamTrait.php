<?php

namespace oihana\commands\enums\traits;

/**
 * The enumeration of the common command's parameters.
 *
 * @package oihana\commands\enums\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait CommandParamTrait
{
    /**
     * The 'after' parameter.
     */
    public const string AFTER = 'after' ;

    /**
     * The 'args' parameter.
     */
    public const string ARGS = 'args' ;
    
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
     * The 'before' parameter.
     */
    public const string BEFORE = 'before' ;

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
     * The 'name' parameter.
     */
    public const string NAME = 'name' ;

    /**
     * The 'run' parameter.
     */
    public const string RUN = 'run' ;

    /**
     * The 'server' parameter.
     */
    public const string SERVER = 'server' ;
}