<?php

namespace oihana\commands\styles;

use JsonException;

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
 * Supports custom palettes and dynamic style injection.
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
 * @package oihana\commands\styles
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
class JsonStyle extends OutputStyle
{
    /**
     * JsonStyle constructor.
     *
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
     * @var string JSON style identifier for circulars.
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
     * Regex patterns for JSON syntax highlighting.
     *
     * @private
     * @var array<string, string>
     *
     * Keys = regular expressions to match JSON tokens.
     * Values = replacement strings with Symfony Console formatting tags.
     */
    private const array PATTERNS =
   [
       '/"(.*?)":/'  => "<"   . self::KEY    . ">\"$1\"</" . self::KEY    . ">:" ,
       '/: "(.*?)"/' => ': <' . self::STRING . '>"$1"</'   . self::STRING . '>'  ,

       '/(?<=[:\[,])\s*(true|false)\b/'  => ' <' . self::BOOL   . '>$1</' . self::BOOL   . '>',
       '/(?<=[:\[,])\s*(null)\b/'        => ' <' . self::NULL   . '>$1</' . self::NULL   . '>',
       '/(?<=[:\[,])\s*(-?\d+\.?\d*)\b/' => ' <' . self::NUMBER . '>$1</' . self::NUMBER . '>',
   ];

    /**
     * Applies JSON syntax highlighting to the Symfony Console output formatter.
     *
     * Merges user-provided styles with {@see self::DEFAULT_STYLES} and registers them.
     *
     * @param array $styles Custom style overrides.
     *
     * @return void
     */
    protected function applyJsonFormatter( array $styles = [] ): void
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
     * @param mixed $data         Any PHP data to encode into JSON.
     * @param int   $jsonOptions  Options for {@see json_encode()}, defaults to:
     *                            - `JSON_PRETTY_PRINT`
     *                            - `JSON_UNESCAPED_UNICODE`
     *                            - `JSON_UNESCAPED_SLASHES`
     * @param int   $verbosity    Minimum verbosity level required to output.
     *                            Defaults to {@see OutputInterface::VERBOSITY_NORMAL}.
     *
     * @return void
     *
     * @example
     * ```php
     * $style->writeJson(['hello' => 'world', 'count' => 5]);
     * ```
     */
    public function writeJson
    (
        mixed $data ,
        int   $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ,
        int   $verbosity   = OutputInterface::VERBOSITY_NORMAL
    )
    : void
    {
        if ( $this->getVerbosity() < $verbosity )
        {
            return;
        }

        $seen = [];
        $output = $this->formatRecursive($data, 0, $seen);
        $this->writeln($output);
    }

    /**
     * Formate récursivement les données PHP en une chaîne JSON colorée.
     *
     * @param mixed $data   Les données à formater.
     * @param int   $indent Le niveau d'indentation actuel.
     * @param array $seen   Tableau pour détecter les références circulaires.
     * @return string
     */
    private function formatRecursive(mixed $data, int $indent, array &$seen): string
    {
        $indentStr = str_repeat(' ', $indent);

        // --- Cas de base (types primitifs) ---
        if (is_null($data)) {
            return '<' . self::NULL . '>null</' . self::NULL . '>';
        }
        if (is_bool($data)) {
            return '<' . self::BOOL . '>' . ($data ? 'true' : 'false') . '</' . self::BOOL . '>';
        }
        if (is_numeric($data)) {
            return '<' . self::NUMBER . '>' . $data . '</' . self::NUMBER . '>';
        }
        if (is_string($data)) {
            // On utilise json_encode sur la chaîne seule pour gérer parfaitement l'échappement.
            return '<' . self::STRING . '>' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</' . self::STRING . '>';
        }

        // --- Cas récursifs (tableaux et objets) ---
        if (is_object($data)) {
            $objectId = spl_object_id($data);
            if (isset($seen[$objectId])) {
                return '<' . self::CIRCULAR . '>[Circular Reference]</' . self::CIRCULAR . '>';
            }
            $seen[$objectId] = true;

            if ($data instanceof JsonSerializable) {
                $data = $data->jsonSerialize();
            } else {
                $data = (array) $data;
            }
        }

        if (is_array($data)) {
            if (empty($data)) {
                return '[]';
            }

            $isAssoc = isAssociative($data);
            $output  = $isAssoc ? "{\n" : "[\n" ;
            $count   = count( $data ) ;

            $i = 0 ;

            foreach ($data as $key => $value)
            {
                $output .= str_repeat(' ', $indent + 4);
                if ($isAssoc) {
                    $output .= '<' . self::KEY . '>' . json_encode((string)$key) . '</' . self::KEY . '>: ';
                }

                $output .= $this->formatRecursive($value, $indent + 4, $seen);

                if (++$i < $count) {
                    $output .= ',';
                }
                $output .= "\n";
            }

            $output .= $indentStr . ($isAssoc ? '}' : ']');

            // Si c'était un objet, on le retire du tableau $seen pour la suite de la récursion
            if (isset($objectId)) {
                unset($seen[$objectId]);
            }

            return $output;
        }

        // Cas d'un type non géré (ex: ressource)
        return '<error>[Unsupported Type]</error>';
    }
}