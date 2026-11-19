<?php

namespace oihana\commands;

use JsonSerializable;
use oihana\reflect\traits\ConstantsTrait;

/**
 * Represents the result of a process execution, including standard output,
 * error output, and exit status, and, when it was run by {@see helpers\runProcess()},
 * whether its time limit ran out and how long it ran.
 *
 * Provides convenience methods for converting the result to an array or JSON.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class Process implements JsonSerializable
{
    /**
     * Initializes the object using an associative array or an object.
     *
     * Only public properties declared in this class will be set.
     * Unknown or non-public properties are silently ignored.
     *
     * @param array|object|null $init Initial values to populate the instance.
     *
     * @example
     * ```php
     * $process = new Process
     * ([
     *     'output' => "Process completed successfully.",
     *     'error'  => null,
     *     'status' => 0,
     * ]);
     *
     * echo $process->toJson(JSON_PRETTY_PRINT);
     * // {
     * //     "output": "Process completed successfully.",
     * //     "error": null,
     * //     "status": 0,
     * //     "timedOut": false,
     * //     "seconds": null
     * // }
     * ```
     */
    public function __construct( array|object|null $init = null )
    {
        if( isset( $init ) )
        {
            foreach ( $init as $key => $value )
            {
                if( property_exists( $this , $key ) )
                {
                    $this->{ $key } = $value ;
                }
            }
        }
    }

    use ConstantsTrait ;

    /**
     * The 'error' key.
     */
    public const string ERROR = 'error' ;

    /**
     * The 'output' key.
     */
    public const string OUTPUT = 'output' ;

    /**
     * The 'seconds' key.
     */
    public const string SECONDS = 'seconds' ;

    /**
     * The 'status' key.
     */
    public const string STATUS = 'status' ;

    /**
     * The 'timedOut' key.
     */
    public const string TIMED_OUT = 'timedOut' ;

    /**
     * The error output of the command (stderr).
     * @var string|null
     */
    public ?string $error = null ;

    /**
     * The standard output of the command (stdout).
     * @var string|null
     */
    public ?string $output = null  ;

    /**
     * How long the process ran, in seconds, or null when nobody measured it.
     * @var ?float
     */
    public ?float $seconds = null ;

    /**
     * The exit status code (0 for success).
     * @var ?int
     */
    public ?int $status = null  ;

    /**
     * Whether the process was stopped because its time limit ran out.
     * @var bool
     */
    public bool $timedOut = false ;

    /**
     * Returns the JSON-serializable data for the object.
     *
     * @return array<string,mixed> Associative array containing output, error, and status.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray() ;
    }

    /**
     * Returns the object as an associative array.
     *
     * @return array<string,mixed> Associative array containing output, error, status,
     *                             timedOut and seconds.
     *
     * @example
     * ```php
     * $process = new Process([
     *     'output' => "OK",
     *     'error'  => null,
     *     'status' => 0,
     * ]);
     * print_r($process->toArray());
     * // [
     * //     "output"   => "OK",
     * //     "error"    => null,
     * //     "status"   => 0,
     * //     "timedOut" => false,
     * //     "seconds"  => null
     * // ]
     * ```
     */
    public function toArray(): array
    {
        return
        [
            self::OUTPUT    => $this->output   ,
            self::ERROR     => $this->error    ,
            self::STATUS    => $this->status   ,
            self::TIMED_OUT => $this->timedOut ,
            self::SECONDS   => $this->seconds  ,
        ] ;
    }

    /**
     * Returns the process result as a JSON string.
     *
     * @param int $flags Optional flags for json_encode (e.g. JSON_PRETTY_PRINT).
     * @return string JSON-encoded representation of the process result.
     *
     * @example
     * ```php
     * $process = new Process
     * ([
     *     'output' => "Task finished.",
     *     'error'  => null,
     *     'status' => 0,
     * ]);
     * echo $process->toJson(JSON_PRETTY_PRINT);
     * ```
     */
    public function toJson( int $flags = 0 ): string
    {
        return json_encode( $this , $flags | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ;
    }
}