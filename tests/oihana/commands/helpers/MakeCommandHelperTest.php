<?php

declare(strict_types=1);

namespace oihana\commands\helpers;

use oihana\commands\options\CommandOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MakeCommandHelperTest extends TestCase
{
    #[Test]
    public function testSimpleStringCommandWithArgs(): void
    {
        $cmd = makeCommand('ls', '-la');
        $this->assertSame('ls -la', $cmd);
    }

    #[Test]
    public function testCommandAsArrayAndArgsArrayFiltersEmptyParts(): void
    {
        $cmd = makeCommand(['wp', 'post', 'list', '', null, '  '], [ '--format=ids', '', null, '  ' ]);
        $this->assertSame('wp post list --format=ids', $cmd);
    }

    #[Test]
    public function testWithOptionsSudoAndOwner(): void
    {
        $options = new CommandOptions([ 'sudo' => true, 'owner' => 'www-data' ]);
        $cmd = makeCommand('wp cache flush', options: $options);
        $this->assertSame('sudo -u www-data wp cache flush', $cmd);
    }

    #[Test]
    public function testPipelinePreviousAndPost(): void
    {
        $cmd = makeCommand('grep "error"', previous: 'cat /var/log/syslog', post: 'wc -l');
        $this->assertSame('cat /var/log/syslog | grep "error" | wc -l', $cmd);
    }

    #[Test]
    public function testPipelineWithoutCentralCommand(): void
    {
        $cmd = makeCommand('', previous: 'echo "start"', post: 'wc -l');
        $this->assertSame('echo "start" | wc -l', $cmd);
    }

    #[Test]
    public function testArgsIgnoredWhenCommandIsNull(): void
    {
        $cmd = makeCommand(null, '--force', previous: 'echo "start"');
        $this->assertSame('echo "start"', $cmd);
    }
}
