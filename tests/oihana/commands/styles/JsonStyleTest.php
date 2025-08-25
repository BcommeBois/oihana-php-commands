<?php

namespace oihana\commands\styles;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

        $data = [
            'key' => 'value',
            'number' => 42,
            'bool' => true,
            'null' => null
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

        $captured = null;
        $output->method('writeln')->willReturnCallback(function($messages) use (&$captured) {
            $captured = $messages;
            return $messages;
        });

        // Using a higher verbosity should prevent output
        $style->writeJson(['key' => 'value'], JSON_PRETTY_PRINT, OutputInterface::VERBOSITY_VERBOSE);

        $this->assertNull($captured, 'Output should not be written if verbosity is higher than current.');
    }

    public function testWriteJsonHandlesEncodingFailure(): void
    {
        $output = $this->getOutputMock();
        $style  = new JsonStyle($output);

        $captured = null;
        $output->method('writeln')->willReturnCallback(function($messages) use (&$captured) {
            $captured = $messages;
            return $messages;
        });

        // Passing a resource triggers json_encode failure
        $resource = fopen('php://memory', 'r');
        $style->writeJson($resource);

        $this->assertStringContainsString('Failed to encode JSON', $captured);

        fclose($resource);
    }
}