<?php

declare(strict_types=1);

namespace tests\oihana\commands\exceptions;

use oihana\commands\exceptions\ExitException;
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
}
