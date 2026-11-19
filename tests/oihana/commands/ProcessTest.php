<?php

declare(strict_types=1);

namespace tests\oihana\commands;

use oihana\commands\Process;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ProcessTest extends TestCase
{
    public function testConstructWithArraySetsProperties()
    {
        $data = [
            'output' => 'output data',
            'error'  => 'error data',
            'status' => 1,
        ];

        $process = new Process($data);

        $this->assertSame('output data', $process->output);
        $this->assertSame('error data', $process->error);
        $this->assertSame(1, $process->status);
    }

    public function testConstructWithObjectSetsProperties()
    {
        $obj = new stdClass();
        $obj->output = 'output';
        $obj->error = 'error';
        $obj->status = 2;

        $process = new Process($obj);

        $this->assertSame('output', $process->output);
        $this->assertSame('error', $process->error);
        $this->assertSame(2, $process->status);
    }

    public function testConstructWithUnknownPropertiesIgnoresThem()
    {
        $data = [
            'output' => 'out',
            'unknown' => 'ignored',
            'status' => 0,
        ];

        $process = new Process($data);

        $this->assertSame('out', $process->output);
        $this->assertNull($process->error);
        $this->assertSame(0, $process->status);
        $this->assertArrayNotHasKey('unknownProperty', get_object_vars($process));
        $this->assertFalse(property_exists($process, 'unknownProperty'));
    }

    public function testDefaultPropertiesAreNull()
    {
        $process = new Process();

        $this->assertNull($process->output);
        $this->assertNull($process->error);
        $this->assertNull($process->status);
    }

    public function testToArrayReturnsCorrectAssociativeArray()
    {
        $process = new Process([
            'output' => 'out',
            'error'  => 'err',
            'status' => 3,
        ]);

        $expected = [
            Process::OUTPUT => 'out',
            Process::ERROR => 'err',
            Process::STATUS => 3,
            Process::TIMED_OUT => false,
            Process::SECONDS => null,
        ];

        $this->assertSame($expected, $process->toArray());
    }

    public function testJsonSerializeReturnsToArray()
    {
        $process = new Process([
            'output' => 'out',
            'error' => 'err',
            'status' => 4,
        ]);

        $this->assertSame($process->toArray(), $process->jsonSerialize());
    }

    public function testToJsonReturnsValidJsonString()
    {
        $process = new Process([
            'output' => "line1\nline2",
            'error' => null,
            'status' => 0,
        ]);

        $json = $process->toJson(JSON_PRETTY_PRINT);
        $expectedArray = [
            'output' => "line1\nline2",
            'error' => null,
            'status' => 0,
            'timedOut' => false,
            'seconds' => null,
        ];

        $this->assertJson($json);
        $this->assertStringContainsString('"output": "line1\nline2"', $json);
        $this->assertStringContainsString('"error": null', $json);
        $this->assertStringContainsString('"status": 0', $json);

        $decoded = json_decode($json, true);
        $this->assertSame($expectedArray, $decoded);
    }

    public function testTimedOutAndSecondsAreSetFromTheArray()
    {
        $process = new Process([
            Process::STATUS    => 124,
            Process::TIMED_OUT => true,
            Process::SECONDS   => 1.5,
        ]);

        $this->assertTrue($process->timedOut);
        $this->assertSame(1.5, $process->seconds);
        $this->assertStringContainsString('"timedOut":true', $process->toJson());
        $this->assertStringContainsString('"seconds":1.5', $process->toJson());
    }

    public function testANewProcessHasNotTimedOut()
    {
        $process = new Process();

        $this->assertFalse($process->timedOut);
        $this->assertNull($process->seconds);
    }
}
