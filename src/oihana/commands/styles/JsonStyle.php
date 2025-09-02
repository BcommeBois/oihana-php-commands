<?php

namespace oihana\commands\styles;

use Generator;
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
     * Gets the fully formatted and colorized JSON as a single string.
     *
     * @param mixed $data The data to format.
     * @return string     The formatted JSON string.
     */
    public function getFormattedJson( mixed $data ): string
    {
        $seen = [] ;
        $generator = $this->formatJsonAsGenerator( $data , 0 , $seen ) ;
        return implode('' , iterator_to_array( $generator , false ) ) ;
    }

    /**
     * Writes JSON data to the console with syntax highlighting.
     *
     * Encodes data to JSON, applies coloring rules, and writes to the console output.
     *
     * This method can operate in two modes:
     * 1. Streaming (default): Writes data piece by piece, ensuring low and
     * constant memory usage. Ideal for large or unknown-size data.
     * 2. Buffering: Formats the entire string in memory before output.
     * Only use this for small, size-controlled data.
     *
     * @param mixed $data      Any PHP data to encode into JSON.
     * @param bool  $stream    Whether to use the memory-efficient streaming mode. Defaults to true.
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
    public function writeJson
    (
        mixed $data ,
        bool  $stream    = true,
        int   $verbosity = OutputInterface::VERBOSITY_NORMAL
    )
    :static
    {
        if ( $this->getVerbosity() < $verbosity )
        {
            return $this ;
        }

        $seen = [];

        if ( $stream )
        {
            // --- Streaming Mode (Low memory) ---
            foreach ($this->formatJsonAsGenerator($data, 0, $seen) as $chunk)
            {
                $this->write($chunk);
            }
            $this->writeln(''); // Final newline
        }
        else
        {
            // --- Buffering Mode (High memory) ---
            $this->writeln($this->getFormattedJson($data));
        }

        return $this;
    }

    /**
     * Recursively formats PHP data, yielding each formatted chunk.
     *
     * This private generator is the core of the formatting logic. It traverses
     * the data and yields pieces of the formatted string, allowing the caller
     * to either stream them or buffer them.
     *
     * @param mixed      $data   The data to format.
     * @param int        $indent The current indentation level.
     * @param array      &$seen  Array for circular reference detection.
     * @return Generator<string>
     */
    private function formatJsonAsGenerator( mixed $data , int $indent , array &$seen ) :Generator
    {
        // --- Base cases (primitive types) ---

        if ( is_null( $data ) )
        {
            yield '<' . self::NULL . '>null</' . self::NULL . '>';
            return;
        }

        if ( is_bool( $data ) )
        {
            yield '<' . self::BOOL . '>' . ($data ? 'true' : 'false') . '</' . self::BOOL . '>';
            return;
        }

        if ( is_numeric( $data ) )
        {
            yield '<' . self::NUMBER . '>' . $data . '</' . self::NUMBER . '>';
            return;
        }

        if ( is_string( $data ) )
        {
            yield '<' . self::STRING . '>' . json_encode( $data , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</' . self::STRING . '>' ;
            return;
        }

        // --- Recursive cases (arrays and objects) ---
        if ( is_object( $data ) )
        {
            $objectId = spl_object_id( $data ) ;
            if ( isset( $seen[ $objectId ] ) )
            {
                yield '<' . self::CIRCULAR . '>[Circular Reference]</' . self::CIRCULAR . '>' ;
                return;
            }
            $seen[ $objectId ] = true ;
            $data = $data instanceof JsonSerializable ? $data->jsonSerialize() : (array) $data ;
        }

        if ( is_array( $data ) )
        {
            if ( empty( $data ) )
            {
                yield '[]' ;
                return;
            }

            $isAssoc = isAssociative($data);
            yield $isAssoc ? "{\n" : "[\n";

            $count = count($data);
            $i = 0;

            foreach ( $data as $key => $value )
            {
                yield str_repeat(' ', $indent + 4 ) ;

                if ( $isAssoc )
                {
                    yield '<' . self::KEY . '>' . json_encode((string)$key) . '</' . self::KEY . '>: ' ;
                }

                // Delegate generation to the recursive call
                yield from $this->formatJsonAsGenerator( $value , $indent + 4 , $seen ) ;

                if ( ++$i < $count )
                {
                    yield ',' ;
                }
                yield "\n" ;
            }

            yield str_repeat(' ' , $indent ) . ($isAssoc ? '}' : ']' ) ;

            if ( isset( $objectId ) )
            {
                unset( $seen[ $objectId ] ) ;
            }

            return ;
        }

        yield '<error>[Unsupported Type]</error>' ;
    }
}