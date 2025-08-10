<?php

declare(strict_types=1);

namespace oihana\commands\exceptions;

use oihana\enums\Char;
use oihana\exceptions\ExceptionTrait;
use PHPUnit\Framework\TestCase;

final class ExitExceptionTest extends TestCase
{
    public function testCanInstantiateWithoutArguments(): void
    {
        $exception = new ExitException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testCanInstantiateWithMessageAndCode(): void
    {
        $exception = new ExitException('Error message', 123);

        $this->assertSame('Error message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
    }

    public function testUsesExceptionTrait(): void
    {
        $traits = class_uses(ExitException::class);
        $this->assertContains(ExceptionTrait::class, $traits);
    }

    public function testToStringReturnsExpectedFormat(): void
    {
        $exception = new ExitException('Test message', 42);

        $expected = Char::LEFT_BRACKET
            . ExitException::class
            . ' code:42 message:Test message'
            . Char::RIGHT_BRACKET;

        $this->assertSame($expected, (string) $exception);
    }
}
