<?php

declare(strict_types=1);

namespace oihana\commands\helpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SilentHelperTest extends TestCase
{
    #[Test]
    public function testSilentFalseDoesNotChangeCommand(): void
    {
        $cmd = 'echo hello';
        $result = silent($cmd, false);
        $this->assertSame('echo hello', $result);
        $this->assertSame('echo hello', $cmd);
    }

    #[Test]
    public function testSilentTrueWithNullCommandReturnsNull(): void
    {
        $cmd = null;
        $result = silent($cmd, true);
        $this->assertNull($result);
        $this->assertNull($cmd);
    }

    #[Test]
    public function testSilentTrueWithEmptyCommandRemainsEmpty(): void
    {
        $cmd = '';
        $result = silent($cmd, true);
        $this->assertSame('', $result);
        $this->assertSame('', $cmd);
    }

    #[Test]
    public function testSilentTrueAppendsRedirectDependingOnOperatingSystem(): void
    {
        $cmd = 'ls -la';
        $result = silent($cmd, true);

        $isWin = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';
        $expected = $isWin ? 'ls -la > NUL 2>&1' : 'ls -la > /dev/null 2>&1';

        $this->assertSame($expected, $result);
        $this->assertSame($expected, $cmd);
    }
}
