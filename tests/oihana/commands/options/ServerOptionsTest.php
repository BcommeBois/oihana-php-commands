<?php

declare(strict_types=1);

namespace tests\oihana\commands\options;

use oihana\commands\options\ServerOptions;
use PHPUnit\Framework\TestCase;

final class ServerOptionsTest extends TestCase
{
    public function testServerConstant(): void
    {
        $this->assertSame('server', ServerOptions::SERVER);
    }

    public function testDefaultConstruction(): void
    {
        $options = new ServerOptions();

        $this->assertNull($options->domain);
        $this->assertNull($options->htdocs);
        $this->assertNull($options->php);
        $this->assertNull($options->subdomain);
        $this->assertNull($options->url);
    }

    public function testConstructionFromArray(): void
    {
        $options = new ServerOptions
        ([
            'domain'    => 'example.com',
            'htdocs'    => '/var/www/html',
            'php'       => '8.3',
            'subdomain' => 'admin',
            'url'       => 'https://admin.example.com',
        ]);

        $this->assertSame('example.com', $options->domain);
        $this->assertSame('/var/www/html', $options->htdocs);
        $this->assertSame('8.3', $options->php);
        $this->assertSame('admin', $options->subdomain);
        $this->assertSame('https://admin.example.com', $options->url);
    }

    public function testGetFullDomainDefault(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'admin';

        $this->assertSame('admin.example.com', $options->getFullDomain());
    }

    public function testGetFullDomainReverse(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'admin';

        $this->assertSame('example.com.admin', $options->getFullDomain(true));
    }

    public function testGetFullDomainCustomSeparator(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'admin';

        $this->assertSame('admin-example.com', $options->getFullDomain(false, '-'));
    }

    public function testGetFullDomainIgnoresNullValues(): void
    {
        $options = new ServerOptions();
        $options->domain = 'example.com';

        $this->assertSame('example.com', $options->getFullDomain());
    }

    public function testGetFullDomainIgnoresEmptyValues(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = '';

        $this->assertSame('example.com', $options->getFullDomain());
    }

    public function testGetFullServerNameStandardSubdomain(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'admin';

        $this->assertSame('admin.example.com', $options->getFullServerName());
    }

    public function testGetFullServerNameWwwSubdomain(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'www';

        $this->assertSame('example.com www.example.com', $options->getFullServerName());
    }

    public function testGetFullServerNameFiltersEmptyDomain(): void
    {
        $options = new ServerOptions();
        $options->subdomain = 'www';

        // domain is null, fullDomain is 'www' → filtered to just 'www'
        $this->assertSame('www', $options->getFullServerName());
    }

    public function testToString(): void
    {
        $options = new ServerOptions();
        $options->domain    = 'example.com';
        $options->subdomain = 'admin';

        $this->assertSame('admin.example.com', (string) $options);
    }
}
