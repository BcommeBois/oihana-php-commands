<?php

namespace oihana\commands\heartbeat;

/**
 * What {@see HeartbeatCommand} remembers of a failing signal, between two passes.
 *
 * One small JSON file per signal, in the directory the command is given:
 *
 * ```json
 * { "since" : 1795000000 , "alerted" : 1795000000 , "error" : "Connection refused" }
 * ```
 *
 * - `since`   : when the watcher stopped answering (the first failed pass) ;
 * - `alerted` : when the last alert left (the last pass that ended on 1) ;
 * - `error`   : the last error, as the command printed it (never the push address).
 *
 * No file : the signal is healthy. The file goes away when the watcher answers again.
 *
 * ⚠️ **A file that cannot be read is a healthy state**, never an exception : the
 * worst it costs is one alert more.
 *
 * @package oihana\commands\heartbeat
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final readonly class HeartbeatState
{
    /**
     * Creates a state.
     *
     * @param int|null $since   When the watcher stopped answering, `null` when it answers.
     * @param int|null $alerted When the last alert left, `null` when none left yet.
     * @param string   $error   The last error.
     */
    public function __construct
    (
        public ?int   $since   = null ,
        public ?int   $alerted = null ,
        public string $error   = ''   ,
    )
    {}

    /**
     * The key of the last alert in the file.
     */
    public const string ALERTED = 'alerted' ;

    /**
     * The key of the last error in the file.
     */
    public const string ERROR = 'error' ;

    /**
     * The key of the first failure in the file.
     */
    public const string SINCE = 'since' ;

    /**
     * The state after the alert of this pass left.
     *
     * @param int $now The time of the pass.
     *
     * @return HeartbeatState
     */
    public function alert( int $now ) :HeartbeatState
    {
        return new HeartbeatState( since : $this->since ?? $now , alerted : $now , error : $this->error ) ;
    }


    /**
     * Removes the file of a state : the watcher answers again.
     *
     * @param string $file The path of the file.
     *
     * @return bool `true` when no file is left.
     */
    public static function clear( string $file ) :bool
    {
        return !is_file( $file ) || @unlink( $file ) ;
    }


    /**
     * The state after a failed pass : the failure keeps its first date.
     *
     * @param int    $now   The time of the pass.
     * @param string $error What failed.
     *
     * @return HeartbeatState
     */
    public function fail( int $now , string $error ) :HeartbeatState
    {
        return new HeartbeatState( since : $this->since ?? $now , alerted : $this->alerted , error : $error ) ;
    }


    /**
     * Whether the failure of this pass has to alert : no alert left yet, or the last one
     * is `$every` seconds old or more.
     *
     * @param int $now   The time of the pass.
     * @param int $every The seconds between two alerts of the same failure.
     *
     * @return bool
     */
    public function isAlertDue( int $now , int $every ) :bool
    {
        return $this->alerted === null || $now - $this->alerted >= $every ;
    }


    /**
     * Whether the watcher is failing.
     *
     * @return bool
     */
    public function isFailing() :bool
    {
        return $this->since !== null ;
    }


    /**
     * Reads the state of a signal ; a missing or unreadable file is a healthy state.
     *
     * @param string $file The path of the file.
     *
     * @return HeartbeatState
     */
    public static function read( string $file ) :HeartbeatState
    {
        $json = is_file( $file ) ? @file_get_contents( $file ) : false ;
        $data = is_string( $json ) ? json_decode( $json , true ) : null ;

        if( !is_array( $data ) )
        {
            return new HeartbeatState() ;
        }

        return new HeartbeatState
        (
            since   : is_int( $data[ self::SINCE   ] ?? null ) ? $data[ self::SINCE   ] : null ,
            alerted : is_int( $data[ self::ALERTED ] ?? null ) ? $data[ self::ALERTED ] : null ,
            error   : is_string( $data[ self::ERROR ] ?? null ) ? $data[ self::ERROR ] : '' ,
        ) ;
    }


    /**
     * Writes the state of a signal.
     *
     * @param string $file The path of the file.
     *
     * @return bool `true` when the file was written.
     */
    public function write( string $file ) :bool
    {
        $json = json_encode
        (
            [ self::SINCE => $this->since , self::ALERTED => $this->alerted , self::ERROR => $this->error ] ,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ;

        return is_string( $json ) && @file_put_contents( $file , $json ) !== false ;
    }
}
