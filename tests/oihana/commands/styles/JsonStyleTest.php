<?php

namespace oihana\commands\styles;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class JsonStyleTest extends TestCase
{
    public function testWriteJsonOutputsCorrectlyWithoutMock(): void
    {
        $output = new BufferedOutput();
        $style  = new JsonStyle($output);

        $data = [
            'key'    => 'value',
            'number' => 42,
            'bool'   => true,
            'null'   => null
        ];

        $captured = $style->getFormattedJson($data);

        $this->assertStringContainsString('<key>"key"</key>:', $captured);
        $this->assertStringContainsString('<str>"value"</str>', $captured);
        $this->assertStringContainsString('<num>42</num>', $captured);
        $this->assertStringContainsString('<bool>true</bool>', $captured);
        $this->assertStringContainsString('<null>null</null>', $captured);
    }

    public function testWriteJsonStreamingModeWritesAndReturnsSelf(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $result = $style->writeJson( [ 'key' => 'value' ] ) ;

        $this->assertSame( $style , $result ) ;

        // The formatter consumes the style tags; an undecorated output renders plain JSON.
        $text = $output->fetch() ;
        $this->assertStringContainsString( '"key": "value"' , $text ) ;
        $this->assertStringEndsWith( PHP_EOL , $text ) ;
    }

    public function testWriteJsonBufferingModeWritesWholeString(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $result = $style->writeJson( [ 'key' => 'value' ] , false ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertStringContainsString( '"value"' , $output->fetch() ) ;
    }

    public function testWriteJsonSkipsOutputWhenVerbosityTooLow(): void
    {
        $output = new BufferedOutput( OutputInterface::VERBOSITY_QUIET ) ;
        $style  = new JsonStyle( $output ) ;

        $result = $style->writeJson( [ 'key' => 'value' ] , true , OutputInterface::VERBOSITY_NORMAL ) ;

        $this->assertSame( $style , $result ) ;
        $this->assertSame( '' , $output->fetch() ) ;
    }

    public function testFormatsPlainObjectByCastingToArray(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $object = new stdClass() ;
        $object->name = 'Oihana' ;

        $captured = $style->getFormattedJson( $object ) ;

        $this->assertStringContainsString( '<key>"name"</key>' , $captured ) ;
        $this->assertStringContainsString( '<str>"Oihana"</str>' , $captured ) ;
    }

    public function testDetectsCircularReference(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $object = new stdClass() ;
        $object->self = $object ;

        $captured = $style->getFormattedJson( $object ) ;

        $this->assertStringContainsString( '[Circular Reference]' , $captured ) ;
        $this->assertStringContainsString( '<circular>' , $captured ) ;
    }

    public function testFormatsJsonSerializableObject(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $object = new class implements JsonSerializable
        {
            public function jsonSerialize(): array
            {
                return [ 'serialized' => 'yes' ] ;
            }
        } ;

        $captured = $style->getFormattedJson( $object ) ;

        $this->assertStringContainsString( '<key>"serialized"</key>' , $captured ) ;
        $this->assertStringContainsString( '<str>"yes"</str>' , $captured ) ;
    }

    public function testFormatsEmptyArray(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $this->assertSame( '[]' , $style->getFormattedJson( [] ) ) ;
    }

    public function testFormatsListArray(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $captured = $style->getFormattedJson( [ 1 , 2 , 3 ] ) ;

        $this->assertStringStartsWith( '[' , $captured ) ;
        $this->assertStringContainsString( '<num>1</num>' , $captured ) ;
        $this->assertStringContainsString( '<num>3</num>' , $captured ) ;
        $this->assertStringNotContainsString( '<key>' , $captured ) ;
    }

    public function testFormatsUnsupportedTypeAsError(): void
    {
        $output = new BufferedOutput() ;
        $style  = new JsonStyle( $output ) ;

        $resource = fopen( 'php://memory' , 'r' ) ;

        $captured = $style->getFormattedJson( $resource ) ;

        fclose( $resource ) ;

        $this->assertStringContainsString( '[Unsupported Type]' , $captured ) ;
    }
}
