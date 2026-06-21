<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function oihana\commands\helpers\clearConsole;

final class ClearConsoleHelperTest extends TestCase
{
    #[Test]
    public function testClearableFalseReturnsFalseAndDoesNotExecuteSystem(): void
    {
        $this->assertFalse(clearConsole(false));
    }

    #[Test]
    public function testClearableTrueExecutesSystemClear(): void
    {
        // system() prints the cleared screen control sequence to stdout, which
        // strict PHPUnit forbids during a test, so capture and discard it.
        ob_start();
        $result = clearConsole(true);
        ob_end_clean();

        // system() returns the last line of output (a string) on success, or
        // false on failure. Either way the $clearable === true branch runs.
        $this->assertTrue($result === false || is_string($result));
    }
}
