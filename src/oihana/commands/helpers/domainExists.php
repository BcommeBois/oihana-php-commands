<?php

namespace oihana\commands\helpers;

/**
 * Checks whether the given domain has valid DNS records (A, AAAA, or CNAME).
 *
 * @param string $domain The domain to check (e.g. "example.com").
 * @return bool True if DNS records exist for the domain, false otherwise.
 *
 * @example
 * ```php
 * $domain = 'example.com';
 *
 * if ( domainExists( $domain ) )
 * {
 *     echo "$domain exists.\n";
 * }
 * else
 * {
 *    echo "$domain does NOT exist.\n";
 * }
 * ```
 *
 * @package oihana\commands\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function domainExists( string $domain ): bool
{
    if ( !filter_var('http://' . $domain , FILTER_VALIDATE_URL ) )
    {
        return false ;
    }

    // Check for common DNS record types
    return checkdnsrr( $domain , 'A'     ) ||  // IPv4
           checkdnsrr( $domain , 'AAAA'  ) ||  // IPv6
           checkdnsrr( $domain , 'CNAME' ) ||  // Alias
           checkdnsrr( $domain , 'MX'    ) ||  // Mail
           checkdnsrr( $domain , 'NS'    ) ;   // Name server
}
