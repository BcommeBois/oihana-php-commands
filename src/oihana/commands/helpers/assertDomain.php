<?php

namespace oihana\commands\helpers;

use InvalidArgumentException;

/**
 * Validates a domain name according to RFC rules and common DNS restrictions.
 *
 * @param string  $domain      The domain name to validate (e.g. "example.com").
 * @param bool    $throw       Whether to throw an exception on invalid domain.
 * @param bool    $requireTld  Whether to require a TLD (default true).
 *
 * @return bool True if valid (or silent if $throw = true and valid).
 *
 * @throws InvalidArgumentException If the domain is invalid and $throw is true.
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * use function oihana\commands\helpers\assertDomain;
 *
 * try
 * {
 *    assertDomain("example.com");         // returns true
 *    assertDomain("sub.domain.org");      // returns true
 *    assertDomain("invalid_domain");      // throws InvalidArgumentException
 * }
 * catch (InvalidArgumentException $e)
 * {
 *    echo "Invalid domain: " . $e->getMessage();
 * }
 *
 * // Without exception throwing
 * $isValid = assertDomain("invalid_domain", false);  // returns false
 * ```
 */
function assertDomain( string $domain , bool $throw = true, bool $requireTld = true ): bool
{
    $domain = trim( $domain ) ;

    if ( $domain === '' )
    {
        if ( $throw )
        {
            throw new InvalidArgumentException("Domain cannot be empty.") ;
        }
        return false ;
    }

    // Must not exceed 253 characters
    if ( strlen( $domain ) > 253 )
    {
        if ( $throw )
        {
            throw new InvalidArgumentException("Domain exceeds maximum length of 253 characters.") ;
        }
        return false ;
    }

    $labels = explode('.' , $domain ) ; // Validate each label (between dots)

    foreach ( $labels as $label )
    {
        // Each label: 1 to 63 characters
        if ( $label === '' || strlen( $label ) > 63 )
        {
            if ( $throw )
            {
                throw new InvalidArgumentException("Invalid label in domain: '$label'." ) ;
            }
            return false ;
        }

        // Valid characters: a-z, A-Z, 0-9, hyphen
        if ( !preg_match('/^[a-zA-Z0-9-]+$/' , $label ) )
        {
            if ( $throw )
            {
                throw new InvalidArgumentException("Invalid characters in domain label: '$label'.") ;
            }
            return false ;
        }

        // Labels can't start or end with a hyphen
        if ( $label[0] === '-' || $label[strlen($label) - 1] === '-' )
        {
            if ( $throw )
            {
                throw new InvalidArgumentException("Label cannot start or end with a hyphen: '$label'." ) ;
            }
            return false;
        }
    }

    // TLD requirement: at least two labels and a valid TLD
    if ( $requireTld && count( $labels ) < 2 )
    {
        if ($throw)
        {
            throw new InvalidArgumentException("Domain must include a top-level domain (e.g. '.com').");
        }
        return false;
    }

    return true;
}
