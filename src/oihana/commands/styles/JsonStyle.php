<?php

namespace oihana\commands\styles;

use JsonSerializable;

use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\StyleOption;
use oihana\reflect\traits\ConstantsTrait;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

use function oihana\core\arrays\isAssociative;

/**
 * A Symfony Console style helper for rendering JSON data with syntax highlighting.
 * Extends {@see OutputStyle} to provide colored and readable JSON output.
 *
 * This class recursively walks through PHP data structures (arrays, objects)
 * and builds a colorized JSON string representation. It handles primitive types,
 * nested structures, and detects circular references in objects.
 *
 * Example:
 * ```php
 * use oihana\commands\styles\JsonStyle;
 *
 * $style = new JsonStyle($output);
 *
 * $style->writeJson
 * ([
 *     'name'   => 'Oihana',
 *     'active' => true,
 *     'count'  => 42,
 *     'tags'   => null
 * ]);
 * ```
 *
 * @package oihana\commands\styles
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
class JsonStyle extends OutputStyle
{
    /**
     * Initializes the style and applies JSON-specific formatting rules.
     *
     * @param OutputInterface $output  Symfony console output implementation.
     * @param array           $styles  Optional custom styles overriding the defaults.
     *
     * @example
     * ```php
     * $style = new JsonStyle( $output ,
     * [
     *     JsonStyle::STRING =>
     *     [
     *         ColorParam::FOREGROUND => Palette::BLUE,
     *         ColorParam::OPTIONS    => [StyleOption::BOLD]
     *     ]
     * ]);
     * ```
     */
    public function __construct( OutputInterface $output , array $styles = [] )
    {
        parent::__construct( $output ) ;
        $this->applyJsonFormatter( $styles ) ;
    }

    use ConstantsTrait ;

    /**
     * @var string JSON style identifier for booleans
     */
    public const string BOOL = 'bool';

    /**
     * @var string JSON style identifier for circular references.
     */
    public const string CIRCULAR = 'circular' ;

    /**
     * @var string JSON style identifier for keys
     */
    public const string KEY = 'key';

    /**
     * @var string JSON style identifier for null values
     */
    public const string NULL = 'null';

    /**
     * @var string JSON style identifier for numbers
     */
    public const string NUMBER = 'num';

    /**
     * @var string JSON style identifier for strings
     */
    public const string STRING = 'str';

    /**
     * The underlying Symfony console output interface.
     *
     * @var OutputInterface
     */
    private OutputInterface $output ;

    /**
     * Default color styles for JSON highlighting.
     *
     * @private
     * @var array<string, array<string, string|array|null>>
     *
     * Structure:
     * ```
     * [
     *     'styleName' => [
     *         ColorParam::FOREGROUND => Palette::*,
     *         ColorParam::BACKGROUND => Palette::*|null,
     *         ColorParam::OPTIONS    => string[]
     *     ],
     * ]
     * ```
     */
    private const array DEFAULT_STYLES =
    [
        self::CIRCULAR => [ ColorParam::FOREGROUND => Palette::RED     , ColorParam::OPTIONS => [ StyleOption::BOLD ] ] ,
        self::KEY      => [ ColorParam::FOREGROUND => Palette::CYAN    , ColorParam::BACKGROUND => null , ColorParam::OPTIONS => [] ],
        self::STRING   => [ ColorParam::FOREGROUND => Palette::GREEN   , ColorParam::BACKGROUND => null , ColorParam::OPTIONS => [] ],
        self::NUMBER   => [ ColorParam::FOREGROUND => Palette::YELLOW  , ColorParam::BACKGROUND => null , ColorParam::OPTIONS => [] ],
        self::BOOL     => [ ColorParam::FOREGROUND => Palette::MAGENTA , ColorParam::BACKGROUND => null , ColorParam::OPTIONS => [] ],
        self::NULL     => [ ColorParam::FOREGROUND => Palette::RED     , ColorParam::BACKGROUND => null , ColorParam::OPTIONS => [ StyleOption::BOLD ] ],
    ] ;

    /**
     * Applies JSON syntax highlighting styles to the Symfony Console output formatter.
     *
     * Merges user-provided styles with {@see self::DEFAULT_STYLES} and registers them.
     *
     * @param array $styles Custom style overrides.
     *
     * @return void
     */
    public function applyJsonFormatter( array $styles = [] ): void
    {
        $merged    = array_replace_recursive( self::DEFAULT_STYLES , $styles ) ;
        $formatter = $this->getFormatter() ;
        foreach ( $merged as $name => $config )
        {
            $formatter->setStyle($name, new OutputFormatterStyle
            (
                foreground : $config[ ColorParam::FOREGROUND ] ?? null ,
                background : $config[ ColorParam::BACKGROUND ] ?? null ,
                options    : $config[ ColorParam::OPTIONS    ] ?? []
            ));
        }
    }

    /**
     * Writes JSON data to the console with syntax highlighting.
     *
     * Encodes data to JSON, applies coloring rules, and writes to the console output.
     *
     * @param mixed $data      Any PHP data to encode into JSON.
     * @param int   $verbosity Minimum verbosity level required to output.
     *                         Defaults to {@see OutputInterface::VERBOSITY_NORMAL}.
     *
     * @return static
     *
     * @example
     * ```php
     * $style->writeJson(['hello' => 'world', 'count' => 5]);
     * ```
     */
    public function writeJson( mixed $data , int $verbosity = OutputInterface::VERBOSITY_NORMAL ) :static
    {
        if ( $this->getVerbosity() < $verbosity )
        {
            return $this ;
        }

        $seen = [];
        $output = $this->formatRecursive($data, 0, $seen);
        $this->writeln($output);
        return $this ;
    }

    /**
     * Recursively formats PHP data into a colorized JSON string.
     *
     * @param mixed $data   The data to format.
     * @param int   $indent The current indentation level.
     * @param array $seen   An array to track seen objects for circular reference detection.
     * @return string
     */
    private function formatRecursive( mixed $data, int $indent, array &$seen ): string
    {
        $indentStr = str_repeat(' ' , $indent ) ;

        // --- Base cases (primitive types) ---

        if (is_null($data)) {
            return '<' . self::NULL . '>null</' . self::NULL . '>';
        }

        if ( is_bool( $data ) )
        {
            return '<' . self::BOOL . '>' . ($data ? 'true' : 'false') . '</' . self::BOOL . '>';
        }

        if ( is_numeric( $data ) )
        {
            return '<' . self::NUMBER . '>' . $data . '</' . self::NUMBER . '>';
        }

        if ( is_string( $data ) )
        {
            // Use json_encode on the string itself to handle escaping perfectly.
            return '<' . self::STRING . '>' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</' . self::STRING . '>';
        }

        // --- Recursive cases (arrays and objects) ---

        if ( is_object( $data ) )
        {
            $objectId = spl_object_id( $data ) ;
            if ( isset( $seen[ $objectId ] ) )
            {
                return '<' . self::CIRCULAR . '>[Circular Reference]</' . self::CIRCULAR . '>';
            }
            $seen[ $objectId ] = true ;
            if ($data instanceof JsonSerializable)
            {
                $data = $data->jsonSerialize();
            }
            else
            {
                $data = (array) $data;
            }
        }

        if ( is_array( $data ) )
        {
            if ( empty( $data ) )
            {
                return '[]' ;
            }

            $isAssoc = isAssociative($data);
            $output  = $isAssoc ? "{\n" : "[\n" ;
            $count   = count( $data ) ;

            $i = 0 ;

            foreach ($data as $key => $value)
            {
                $output .= str_repeat(' ' , $indent + 4 ) ;
                if ( $isAssoc )
                {
                    $output .= '<' . self::KEY . '>' . json_encode((string)$key) . '</' . self::KEY . '>: ' ;
                }

                $output .= $this->formatRecursive( $value , $indent + 4 , $seen ) ;

                if ( ++$i < $count )
                {
                    $output .= ',' ;
                }
                $output .= "\n" ;
            }

            $output .= $indentStr . ( $isAssoc ? '}' : ']' ) ;

            // If it was an object, remove it from the seen array to allow the same object
            // to be rendered again in a different part of the data structure.
            if ( isset( $objectId ) )
            {
                unset( $seen[ $objectId ] ) ;
            }

            return $output ;
        }

        // Handle unsupported types (e.g., resources).
        return '<error>[Unsupported Type]</error>';
    }
}