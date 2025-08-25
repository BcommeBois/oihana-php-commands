<?php

namespace oihana\commands\styles;

use JsonSerializable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use stdClass;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsonStyleTest extends TestCase
{
    private function getOutputMock(): MockObject
    {
        $formatterMock = $this->createMock(OutputFormatterInterface::class);
        $formatterMock->expects($this->any())->method('setStyle');

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->method('getFormatter')->willReturn($formatterMock);
        $outputMock->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $outputMock->method('writeln')->willReturnCallback(function($messages) { return $messages; });

        return $outputMock;
    }

    public function testWriteJsonOutputsCorrectly(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $data =
        [
            'key'    => 'value',
            'number' => 42,
            'bool'   => true,
            'null'   => null
        ];

        // Capture output via a custom callback
        $captured = null;
        $output->method('writeln')->willReturnCallback(function($messages) use (&$captured) {
            $captured = $messages;
            return $messages;
        });

        $style->writeJson($data);

        $this->assertIsString($captured);
        $this->assertStringContainsString('<key>"key"</key>:', $captured);
        $this->assertStringContainsString('<str>"value"</str>', $captured);
        $this->assertStringContainsString('<num>42</num>', $captured);
        $this->assertStringContainsString('<bool>true</bool>', $captured);
        $this->assertStringContainsString('<null>null</null>', $captured);
    }

    public function testWriteJsonRespectsVerbosity(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $output->expects($this->never())->method('writeln') ;

        $style->writeJson(['key' => 'value'], JSON_PRETTY_PRINT, OutputInterface::VERBOSITY_VERBOSE);
    }

    public function testWriteJsonHandlesEncodingFailure(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $captured = '';
        $output->method('writeln')->willReturnCallback(function ($messages) use (&$captured) {
            $captured = $messages;
        });

        // Passer une ressource provoque une erreur d'encodage JSON
        $resource = fopen('php://memory', 'r');
        $style->writeJson($resource);
        fclose($resource);

        $this->assertStringContainsString('<error>[Unsupported Type]</error>', $captured);
    }

    // --- Nouveaux tests ajoutés ---

    /**
     * Teste la détection et la gestion correcte des références circulaires.
     */
    public function testWriteJsonHandlesCircularReference(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $captured = '';
        $output->method('writeln')->willReturnCallback(function ($messages) use (&$captured) {
            $captured = $messages;
        });

        // Création d'une référence circulaire
        $obj       = new stdClass();
        $obj->self = $obj;

        $style->writeJson($obj);

        $this->assertStringContainsString('[Circular Reference]', $captured);
    }

    /**
     * Teste que les objets implémentant JsonSerializable sont correctement traités.
     */
    public function testWriteJsonHandlesJsonSerializable(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $data = new class implements JsonSerializable
        {
            public function jsonSerialize(): array
            {
                return ['foo' => 'bar', 'count' => 123];
            }
        };

        $captured = '';
        $output->method('writeln')->willReturnCallback(function ($messages) use (&$captured)
        {
            $captured .= $messages . "\n";
        });

        $style->writeJson($data);

        $this->assertStringContainsString('<key>"foo"</key>:', $captured);
        $this->assertStringContainsString('<str>"bar"</str>', $captured);
        $this->assertStringContainsString('<num>123</num>', $captured);
    }

    /**
     * Teste l'affichage correct des structures de données imbriquées.
     */
    public function testWriteJsonWithNestedData(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle( $output );

        $data =
        [
            'level1' =>
            [
                'level2_key'   => 'level2_value',
                'level2_array' => [ 1 , 2 , 3 ] ,
            ],
            'active' =>  true ,
        ];

        $captured = '';
        $output->method('writeln')->willReturnCallback(function ($messages) use (&$captured) {
            $captured .= $messages . "\n";
        });

        $style->writeJson($data);

        // Vérifie les différents niveaux de la structure
        $this->assertStringContainsString('<key>"level1"</key>:', $captured);
        $this->assertStringContainsString('<key>"level2_key"</key>:', $captured);
        $this->assertStringContainsString('<str>"level2_value"</str>', $captured);
        $this->assertStringContainsString('<num>2</num>', $captured); // Vérifie un élément du tableau imbriqué
        $this->assertStringContainsString('<bool>true</bool>', $captured);
    }
}