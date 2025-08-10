<?php

declare(strict_types=1);

namespace oihana\commands\helpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClearConsoleHelperTest extends TestCase
{
    #[Test]
    public function testClearableFalseReturnsFalseAndDoesNotExecuteSystem(): void
    {
        $this->assertFalse(clearConsole(false));
    }
}
