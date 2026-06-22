<?php

declare(strict_types=1);

namespace tests\oihana\commands\options;

use oihana\commands\options\ChownOptions;
use PHPUnit\Framework\TestCase;

use function oihana\files\isMac;

final class ChownOptionsTest extends TestCase
{
    public function testDefaultConstruction(): void
    {
        $options = new ChownOptions();

        $this->assertNull($options->from);
        $this->assertNull($options->group);
        $this->assertNull($options->noDereference);
        $this->assertNull($options->owner);
        $this->assertNull($options->path);
        $this->assertNull($options->reference);
        $this->assertNull($options->recursive);
        $this->assertNull($options->sudo);
        $this->assertNull($options->verbose);
    }

    public function testConstructionFromArray(): void
    {
        $options = new ChownOptions
        ([
            'from'          => 'root:root',
            'group'         => 'www-data',
            'noDereference' => true,
            'owner'         => 'www-data',
            'path'          => '/var/www/html',
            'reference'     => '/etc/passwd',
            'recursive'     => true,
            'sudo'          => true,
            'verbose'       => true,
        ]);

        $this->assertSame('root:root', $options->from);
        $this->assertSame('www-data', $options->group);
        $this->assertTrue($options->noDereference);
        $this->assertSame('www-data', $options->owner);
        $this->assertSame('/var/www/html', $options->path);
        $this->assertSame('/etc/passwd', $options->reference);
        $this->assertTrue($options->recursive);
        $this->assertTrue($options->sudo);
        $this->assertTrue($options->verbose);
    }

    public function testToStringWithRecursiveAndVerbose(): void
    {
        $options = new ChownOptions
        ([
            'recursive' => true,
            'verbose'   => true,
            'group'     => 'www-data',
            'owner'     => 'www-data',
            'path'      => '/var/www/html',
        ]);

        if (isMac())
        {
            // BSD chown: -R -v ; group/owner/path are excluded.
            $this->assertSame('-R -v', (string) $options);
        }
        else
        {
            // GNU chown: --recursive --verbose ; group/owner/path are excluded.
            $this->assertSame('--recursive --verbose', (string) $options);
        }
    }

    public function testToStringEmptyWhenNoFlags(): void
    {
        $options = new ChownOptions();
        $this->assertSame('', (string) $options);
    }

    public function testToStringExcludesGroupOwnerPathSudo(): void
    {
        $options = new ChownOptions
        ([
            'group' => 'www-data',
            'owner' => 'www-data',
            'path'  => '/var/www/html',
            'sudo'  => true,
        ]);

        // group, owner, path, sudo are all excluded → empty.
        $this->assertSame('', (string) $options);
    }

    public function testToStringWithFromAndReference(): void
    {
        $options = new ChownOptions
        ([
            'from'      => 'root:root',
            'reference' => '/etc/passwd',
        ]);

        $result = (string) $options;

        if (isMac())
        {
            // BSD chown has no --from / --reference equivalent, so on macOS these
            // GNU-only options are excluded entirely → empty fragment.
            $this->assertSame('', $result);
        }
        else
        {
            // GNU chown: long options are hyphenated as expected.
            $this->assertStringContainsString('--from="root:root"', $result);
            $this->assertStringContainsString('--reference="/etc/passwd"', $result);
        }
    }
}
