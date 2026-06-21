<?php

declare(strict_types=1);

namespace tests\oihana\commands\enums;

use PHPUnit\Framework\TestCase;

use oihana\commands\enums\ExitCode;

final class ExitCodeTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(ExitCode::class . '::SUCCESS'), 'The SUCCESS constant must be defined.');
        $this->assertTrue(defined(ExitCode::class . '::FAILURE'), 'The FAILURE constant must be defined.');
        $this->assertTrue(defined(ExitCode::class . '::INVALID'), 'The INVALID constant must be defined.');
        $this->assertTrue(defined(ExitCode::class . '::SIGKILL'), 'The SIGKILL constant must be defined.');
    }

    public function testConstantValues(): void
    {
        $this->assertSame(0, ExitCode::SUCCESS, 'SUCCESS must equal 0.');
        $this->assertSame(1, ExitCode::FAILURE, 'FAILURE must equal 1.');
        $this->assertSame(2, ExitCode::INVALID, 'INVALID must equal 2.');
        $this->assertSame(126, ExitCode::CANNOT_EXECUTE, 'CANNOT_EXECUTE must equal 126.');
        $this->assertSame(127, ExitCode::COMMAND_NOT_FOUND, 'COMMAND_NOT_FOUND must equal 127.');
        $this->assertSame(130, ExitCode::SIGINT, 'SIGINT must equal 130.');
        $this->assertSame(137, ExitCode::SIGKILL, 'SIGKILL must equal 137.');
        $this->assertSame(143, ExitCode::SIGTERM, 'SIGTERM must equal 143.');
        $this->assertSame(255, ExitCode::EXIT_STATUS_OUT_OF_RANGE, 'EXIT_STATUS_OUT_OF_RANGE must equal 255.');
    }

    public function testGetAllContainsConstants(): void
    {
        $constants = ExitCode::getAll();

        $this->assertArrayHasKey('SUCCESS', $constants, 'getAll() must contain the SUCCESS key.');
        $this->assertArrayHasKey('SIGKILL', $constants, 'getAll() must contain the SIGKILL key.');
        $this->assertSame(0, $constants['SUCCESS'], 'getAll()["SUCCESS"] must equal 0.');
    }

    public function testGetCodeForSignalReturnsCode(): void
    {
        $this->assertSame(143, ExitCode::getCodeForSignal('sigterm'), 'getCodeForSignal() must be case-insensitive and return 143 for sigterm.');
        $this->assertSame(137, ExitCode::getCodeForSignal('SIGKILL'), 'getCodeForSignal() must return 137 for SIGKILL.');
    }

    public function testGetCodeForSignalReturnsNullWhenUnknown(): void
    {
        $this->assertNull(ExitCode::getCodeForSignal('NOT_A_SIGNAL'), 'getCodeForSignal() must return null for an unknown signal.');
    }

    public function testGetSignalNameReturnsName(): void
    {
        $this->assertSame('SIGKILL', ExitCode::getSignalName(137), 'getSignalName(137) must return "SIGKILL".');
        $this->assertSame('SIGTERM', ExitCode::getSignalName(143), 'getSignalName(143) must return "SIGTERM".');
    }

    public function testGetSignalNameReturnsNullWhenNotASignal(): void
    {
        $this->assertNull(ExitCode::getSignalName(0), 'getSignalName(0) must return null.');
    }
}
