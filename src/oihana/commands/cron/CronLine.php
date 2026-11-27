<?php

namespace oihana\commands\cron;

use InvalidArgumentException;

/**
 * Reads and marks the crontab lines managed by the cron tools of this library.
 *
 * A managed line ends with a marker comment: `# oihana-cron:<scope>:<name>`. The scope groups
 * the lines of one application, or of one instance of it — two instances on the same machine
 * (`myapp-production`, `myapp-staging`) share the crontab of their user and never see the lines
 * of each other. The name tells the job apart inside its scope.
 *
 * A line without a marker, or with the marker of another scope, belongs to someone else: nothing
 * here ever reads it as a job.
 *
 * A disabled job keeps its line, commented out: `# 0 6 * * * /srv/app/check.sh # oihana-cron:myapp:check`.
 *
 * @package oihana\commands\cron
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
final class CronLine
{
    /**
     * The pattern of a scope and of a name: letters, digits, `-` and `_`, starting with a letter or a digit.
     */
    public const string IDENTIFIER = '[A-Za-z0-9][A-Za-z0-9_-]*' ;

    /**
     * The word that opens the marker of a managed line.
     */
    public const string MARKER = 'oihana-cron' ;

    /**
     * Comments a line out, which disables its job.
     *
     * @param string $line The line.
     *
     * @return string The line commented out, or the line itself when it already is.
     */
    public static function disable( string $line ) :string
    {
        return self::isDisabled( $line ) ? $line : '# ' . $line ;
    }

    /**
     * Uncomments a line, which enables its job.
     *
     * @param string $line The line.
     *
     * @return string The line without its leading `#`, or the line itself when it has none.
     */
    public static function enable( string $line ) :string
    {
        return self::isDisabled( $line ) ? (string) preg_replace( '/^\s*#\s*/' , '' , $line , 1 ) : $line ;
    }

    /**
     * Tells whether a line is commented out.
     *
     * @param string $line The line.
     *
     * @return bool True when the first character that is not a space is `#`.
     */
    public static function isDisabled( string $line ) :bool
    {
        return preg_match( '/^\s*#/' , $line ) === 1 ;
    }

    /**
     * Tells whether a string can be a scope or a job name.
     *
     * @param string $value The string.
     *
     * @return bool True for letters, digits, `-` and `_`, starting with a letter or a digit.
     */
    public static function isIdentifier( string $value ) :bool
    {
        return preg_match( '/^' . self::IDENTIFIER . '$/' , $value ) === 1 ;
    }

    /**
     * The marker comment that ends the line of a job.
     *
     * @param string $scope The scope of the job.
     * @param string $name  The name of the job.
     *
     * @return string The marker, e.g. `# oihana-cron:myapp-staging:check`.
     *
     * @throws InvalidArgumentException When the scope or the name is not an identifier.
     */
    public static function marker( string $scope , string $name ) :string
    {
        foreach( [ 'scope' => $scope , 'name' => $name ] as $what => $value )
        {
            if( !self::isIdentifier( $value ) )
            {
                throw new InvalidArgumentException( sprintf
                (
                    'The cron %s "%s" is not valid: use letters, digits, "-" and "_", starting with a letter or a digit.' ,
                    $what ,
                    $value
                )) ;
            }
        }

        return sprintf( '# %s:%s:%s' , self::MARKER , $scope , $name ) ;
    }

    /**
     * The name of the job a line holds, when the line belongs to the given scope.
     *
     * @param string $line  The line, as the crontab holds it.
     * @param string $scope The scope.
     *
     * @return string|null The name of the job, or null when the line has no marker or the marker of another scope.
     */
    public static function name( string $line , string $scope ) :?string
    {
        $pattern = sprintf( '/\s#\s*%s:(%s):(%s)\s*$/' , self::MARKER , self::IDENTIFIER , self::IDENTIFIER ) ;

        if( preg_match( $pattern , $line , $matches ) !== 1 || $matches[ 1 ] !== $scope )
        {
            return null ;
        }

        return $matches[ 2 ] ;
    }
}
