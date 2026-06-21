<?php

declare(strict_types=1);

namespace tests\oihana\commands\exceptions;

use Exception;
use RuntimeException;

use PHPUnit\Framework\TestCase;

use oihana\commands\exceptions\MissingPassphraseException;

final class MissingPassphraseExceptionTest extends TestCase
{
    public function testIsAnException(): void
    {
        $exception = new MissingPassphraseException();

        $this->assertInstanceOf(Exception::class, $exception, 'MissingPassphraseException must extend Exception.');
    }

    public function testDefaultMessageAndCode(): void
    {
        $exception = new MissingPassphraseException();

        $this->assertSame('The passphrase is required.', $exception->getMessage(), 'The default message must be set.');
        $this->assertSame(0, $exception->getCode(), 'The default code must be 0.');
        $this->assertNull($exception->getPrevious(), 'The default previous exception must be null.');
    }

    public function testCustomMessageCodeAndPrevious(): void
    {
        $previous  = new RuntimeException('root cause');
        $exception = new MissingPassphraseException('Custom message', 42, $previous);

        $this->assertSame('Custom message', $exception->getMessage(), 'The custom message must be set.');
        $this->assertSame(42, $exception->getCode(), 'The custom code must be set.');
        $this->assertSame($previous, $exception->getPrevious(), 'The previous exception must be set.');
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(MissingPassphraseException::class);
        $this->expectExceptionMessage('The passphrase is required.');

        throw new MissingPassphraseException();
    }
}
