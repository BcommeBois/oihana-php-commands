<?php

namespace oihana\commands\styles;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

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
}