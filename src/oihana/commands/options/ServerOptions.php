<?php

namespace oihana\commands\options;

use oihana\enums\Char;

/**
 * Represents server-related configuration options for a website.
 *
 * This class extends {@see Options} to store and manipulate key information
 * about a website hosted on a server, such as its domain, subdomain, URL,
 * PHP version, and document root path (htdocs).
 *
 * It provides helper methods to construct a full domain name expression
 * (with customizable ordering and separators) and to generate a complete
 * server name suitable for server configurations (e.g., virtual hosts).
 *
 * ## Properties
 * - {@see self::$domain}    The website's main domain.
 * - {@see self::$subdomain} The website's subdomain (e.g., "www", "admin").
 * - {@see self::$url}       The full URL of the website.
 * - {@see self::$php}       The PHP version used by the site.
 * - {@see self::$htdocs}    The document root path on the server.
 *
 * ## Usage example
 * ```php
 * use oihana\commands\options\ServerOptions;
 *
 * $opt = new ServerOptions();
 * $opt->domain    = 'example.com';
 * $opt->subdomain = 'admin';
 *
 * echo $opt->getFullDomain()           . PHP_EOL; // "admin.example.com"
 * echo $opt->getFullDomain(true)       . PHP_EOL; // "example.com.admin"
 * echo $opt->getFullDomain(false, '-') . PHP_EOL; // "admin-example.com"
 *
 * echo $opt->getFullServerName()       . PHP_EOL; // "admin.example.com"
 *
 * // When subdomain is 'www'
 * $opt->subdomain = 'www';
 * echo $opt->getFullServerName()       . PHP_EOL; // "example.com www.example.com"
 * ```
 *
 * @package oihana\commands\options
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ServerOptions extends Options
{
    /**
     * The 'server' constant.
     */
    public const string SERVER = 'server' ;

    /**
     * The website domain.
     * @var ?string
     */
    public ?string $domain = null ;

    /**
     * The htdocs directory of the website on the server.
     * @var ?string
     */
    public ?string $htdocs = null ;

    /**
     * The php version.
     * @var ?string
     */
    public ?string $php = null ;

    /**
     * The website subdomain.
     * @var ?string
     */
    public ?string $subdomain = null ;

    /**
     * The url of the website.
     * @var ?string
     */
    public ?string $url = null ;

    /**
     * Returns the full domain expression.
     *
     * Constructs the full domain name by combining the subdomain and domain properties.
     * If `$reverse` is false (default), the order is: `subdomain.domain`
     * If `$reverse` is true, the order is: `domain.subdomain`
     * Empty or null values are ignored in the final result.
     *
     * @param bool   $reverse   Indicates if the domain is before the subdomain in the final expression or not.
     * @param string $separator The separator between the domain and the subdomain components (default is '.').
     *
     * @return string The full domain as a string.
     *
     * @example
     * ```php
     * $opt = new ServerOptions ;
     * $opt->domain    = 'example.com' ;
     * $opt->subdomain = 'admin' ;
     *
     * echo $opt->getFullDomain()           . PHP_EOL ; // 'admin.example.com'
     * echo $opt->getFullDomain(true)       . PHP_EOL ; // 'example.com.admin'
     * echo $opt->getFullDomain(false, '-') . PHP_EOL ; // 'admin-example.com'
     * ```
     */
    public function getFullDomain( bool $reverse = false , string $separator = Char::DOT ):string
    {
        $domain = array_filter( [ $this->subdomain , $this->domain ] , fn( $value ) => $value !== null && $value !== Char::EMPTY ) ;
        if( $reverse )
        {
            $domain = array_reverse( $domain );
        }
        return implode( $separator,  $domain ) ;
    }

    /**
     * Returns the full servername expression.
     * @return string
     */
    public function getFullServerName():string
    {
        $fullDomain = $this->getFullDomain() ;
        if( $this->subdomain === 'www' )
        {
            $domains = [ $this->domain , $fullDomain ] ;
        }
        else
        {
            $domains = [ $fullDomain ] ;
        }

        $domains = array_filter( $domains , fn( $value ) => $value !== null && $value !== Char::EMPTY ) ;

        return implode( Char::SPACE,  $domains ) ;
    }

    /**
     * Returns the string representation of the object.
     * @return string
     */
    public function __toString() : string
    {
        return $this->getFullDomain() ;
    }
}