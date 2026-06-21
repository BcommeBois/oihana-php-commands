<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\commands\helpers\comment;
use function oihana\commands\helpers\error;
use function oihana\commands\helpers\info;
use function oihana\commands\helpers\notice;
use function oihana\commands\helpers\success;
use function oihana\commands\helpers\warning;

final class OutputShortcutsHelperTest extends TestCase
{
    public function testComment(): void
    {
        $this->assertSame(
            '<fg=magenta;options=underscore>This is a comment</>',
            comment( 'This is a comment' )
        );
    }

    public function testError(): void
    {
        $this->assertSame(
            '<fg=red;options=bold>Something went wrong!</>',
            error( 'Something went wrong!' )
        );
    }

    public function testInfo(): void
    {
        $this->assertSame(
            '<fg=cyan>This is an informational message</>',
            info( 'This is an informational message' )
        );
    }

    public function testNotice(): void
    {
        $this->assertSame(
            '<fg=blue;options=bold>Take note of this!</>',
            notice( 'Take note of this!' )
        );
    }

    public function testSuccess(): void
    {
        $this->assertSame(
            '<fg=green>Operation completed successfully!</>',
            success( 'Operation completed successfully!' )
        );
    }

    public function testWarning(): void
    {
        $this->assertSame(
            '<fg=yellow>Be careful!</>',
            warning( 'Be careful!' )
        );
    }
}
