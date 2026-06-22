<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

use function oihana\commands\helpers\formatJson;

// formatJson.php is not part of the composer "files" autoload list, so it
// must be required explicitly for the function to be available in tests.
require_once __DIR__ . '/../../../../src/oihana/commands/helpers/formatJson.php';

final class FormatJsonHelperTest extends TestCase
{
    /**
     * Wraps the given data in a SymfonyStyle backed by a BufferedOutput and
     * returns the produced (uncolored) console output.
     */
    private function render( mixed $data ): string
    {
        $output = new BufferedOutput();
        $io     = new SymfonyStyle( new ArrayInput( [] ), $output );

        formatJson( $io, $data );

        return $output->fetch();
    }

    public function testFormatsAssociativeArray(): void
    {
        $result = $this->render( [ 'name' => 'bob', 'n' => 3, 'b' => true, 'nil' => null ] );

        // Associative arrays are wrapped in curly braces.
        $this->assertStringStartsWith( '{', $result );
        $this->assertStringContainsString( '"name": ', $result );
        $this->assertStringContainsString( 'bob', $result );
        $this->assertStringContainsString( '"n": ', $result );
        $this->assertStringContainsString( '3', $result );
        $this->assertStringContainsString( 'true', $result );
        $this->assertStringContainsString( 'null', $result );
        $this->assertStringContainsString( '}', $result );
    }

    public function testFormatsListArray(): void
    {
        $result = $this->render( [ 1, 2, 3 ] );

        // List (sequential) arrays are wrapped in square brackets.
        $this->assertStringStartsWith( '[', $result );
        $this->assertStringContainsString( '1', $result );
        $this->assertStringContainsString( '2', $result );
        $this->assertStringContainsString( '3', $result );
        $this->assertStringContainsString( ']', $result );
    }

    public function testFormatsNestedStructure(): void
    {
        $result = $this->render( [ 'outer' => [ 'inner' => [ 1, 2 ] ] ] );

        $this->assertStringContainsString( '"outer": ', $result );
        $this->assertStringContainsString( '"inner": ', $result );
        $this->assertStringContainsString( '[', $result );
        $this->assertStringContainsString( '{', $result );
    }

    public function testFormatsStringScalar(): void
    {
        $result = $this->render( 'he"llo' );
        // Strings are escaped with addslashes and wrapped in quotes.
        $this->assertSame( "\"he\\\"llo\"\n", $result );
    }

    public function testFormatsNumericScalar(): void
    {
        $this->assertSame( "42\n", $this->render( 42 ) );
        $this->assertSame( "3.5\n", $this->render( 3.5 ) );
    }

    public function testFormatsBooleanScalar(): void
    {
        $this->assertSame( "true\n", $this->render( true ) );
        $this->assertSame( "false\n", $this->render( false ) );
    }

    public function testFormatsNullScalar(): void
    {
        $this->assertSame( "null\n", $this->render( null ) );
    }

    /**
     * Default branch: a value that is neither array, string, numeric, bool nor
     * null (here a resource) falls through to the `<fg=default>` writer.
     */
    public function testFormatsDefaultBranchWithResource(): void
    {
        $resource = fopen( 'php://memory', 'r' );
        $result   = $this->render( $resource );
        $this->assertStringContainsString( 'Resource id #', $result );
        fclose( $resource );
    }

    /**
     * A JsonSerializable object is expanded through jsonSerialize().
     */
    public function testFormatsJsonSerializableObject(): void
    {
        $object = new class implements JsonSerializable
        {
            public function jsonSerialize(): mixed
            {
                return [ 'x' => 1, 'y' => [ 'z' => true ] ];
            }
        };

        $result = $this->render( $object );

        $this->assertStringContainsString( '"x": ', $result );
        $this->assertStringContainsString( '"y": ', $result );
        $this->assertStringContainsString( '"z": ', $result );
        $this->assertStringContainsString( 'true', $result );
    }

    /**
     * A non-JsonSerializable object with NO properties is converted to an empty
     * associative array and rendered as an empty object `{}`.
     */
    public function testFormatsNonJsonSerializableEmptyObjectAsEmptyBraces(): void
    {
        $object = new class {};

        $result = $this->render( $object );

        $this->assertStringStartsWith( '{', trim( $result ) );
        $this->assertStringContainsString( '}', $result );
    }

    /**
     * A non-JsonSerializable object is converted to an associative array of its
     * reflected properties and rendered as a JSON object.
     */
    public function testFormatsNonJsonSerializableObjectWithProperties(): void
    {
        $object = new class
        {
            public int $a = 1;
            public string $b = 'x';
        };

        $result = $this->render( $object );

        $this->assertStringContainsString( '"a": ', $result );
        $this->assertStringContainsString( '1', $result );
        $this->assertStringContainsString( '"b": ', $result );
        $this->assertStringContainsString( 'x', $result );
    }

    /**
     * Exercises the trailing `if ( is_object( $data ) ) { unset( $seen[...] ) }`
     * cleanup: a JsonSerializable whose jsonSerialize() returns a *stringable*
     * (non-array, non-JsonSerializable) object leaves `$data` an object at the
     * end, which the default branch renders via __toString and then unsets from
     * the seen map.
     */
    public function testJsonSerializableReturningStringableObjectIsRenderedAndUnset(): void
    {
        $stringable = new class
        {
            public function __toString(): string
            {
                return 'STR';
            }
        };

        $object = new class( $stringable ) implements JsonSerializable
        {
            public function __construct( private object $inner ) {}

            public function jsonSerialize(): mixed
            {
                return $this->inner;
            }
        };

        $this->assertStringContainsString( 'STR', $this->render( $object ) );
    }

    /**
     * The circular-reference branch is reachable when the same object appears
     * twice: the first occurrence marks it as "seen", the second renders the
     * `[Circular Reference]` marker (using the valid `comment` style) instead
     * of recursing.
     */
    public function testCircularReferenceBranchRendersMarker(): void
    {
        $object = new class implements JsonSerializable
        {
            public int $v = 1;

            public function jsonSerialize(): mixed
            {
                return [ 'v' => $this->v ];
            }
        };

        $result = $this->render( [ $object, $object ] );

        $this->assertStringContainsString( '[Circular Reference]', $result );
    }
}
