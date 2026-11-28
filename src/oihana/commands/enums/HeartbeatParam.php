<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The initialization parameters of the {@see \oihana\commands\heartbeat\HeartbeatCommand}.
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
class HeartbeatParam
{
    use ConstantsTrait ;

    /**
     * The seconds between two alerts of the same failure (3600 by default).
     */
    public const string ALERT_EVERY = 'alertEvery' ;

    /**
     * The HTTP client that calls the push addresses: a PSR-18 `ClientInterface`.
     */
    public const string CLIENT = 'client' ;

    /**
     * The factory of the requests: a PSR-17 `RequestFactoryInterface`.
     */
    public const string REQUEST_FACTORY = 'requestFactory' ;

    /**
     * The signals: their name → their push address.
     */
    public const string SIGNALS = 'signals' ;

    /**
     * The directory where the failure of each signal is remembered. Without it, every failure alerts.
     */
    public const string STATE_DIR = 'stateDir' ;
}
