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
}
