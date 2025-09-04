<?php

namespace oihana\commands\enums\outputs;

use InvalidArgumentException;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Defines the available colors for Symfony Console output.
 *
 * @package oihana\commands\enums\outputs
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
class Palette
{
    use ConstantsTrait ;

    /**
     * @var string Black color
     */
    public const string BLACK = 'black';

    /**
     * @var string Blue color
     */
    public const string BLUE = 'blue';

    /**
     * @var string Cyan color
     */
    public const string CYAN = 'cyan';

    /**
     * @var string Default terminal color
     */
    public const string DEFAULT = 'default';

    /**
     * @var string Green color
     */
    public const string GREEN = 'green';

    /**
     * @var string Magenta color
     */
    public const string MAGENTA = 'magenta';

    /**
     * @var string Red color
     */
    public const string RED = 'red';

    /**
     * @var string Yellow color
     */
    public const string YELLOW = 'yellow';

    /**
     * @var string White color
     */
    public const string WHITE = 'white';

    /**
     * Indicates if the passed value is a valid color.
     * @param mixed $value
     * @return void
     */
    public static function assertColor( mixed $value ) :void
    {
        $check = match( $value )
        {
            self::BLACK ,
            self::BLUE,
            self::CYAN,
            self::GREEN,
            self::MAGENTA,
            self::RED,
            self::YELLOW,
            self::WHITE => true  ,
            default     => false ,
        };

        if( !$check )
        {
            throw new InvalidArgumentException( sprintf( "Invalid foreground color '%s'" , $value ) ) ;
        }
    }
}