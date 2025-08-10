<?php

declare(strict_types=1);

namespace oihana\commands\helpers;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AssertDomainHelperTest extends TestCase
{
    #[Test]
    public function testValidDomainsReturnTrue(): void
    {
        $this->assertTrue(assertDomain('example.com'));
        $this->assertTrue(assertDomain('sub.domain.org'));
        $this->assertTrue(assertDomain('a-b-c.example'));
    }

    #[Test]
    public function testEmptyDomainThrowsWhenThrowEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain cannot be empty.');
        assertDomain('');
    }

    #[Test]
    public function testEmptyDomainReturnsFalseWhenThrowDisabled(): void
    {
        $this->assertFalse(assertDomain('', false));
    }

    #[Test]
    public function testTooLongDomain(): void
    {
        $label = str_repeat('a', 63);
        $domain = $label . '.' . $label . '.' . $label . '.abcd';
        // make it exceed 253
        $domain = str_repeat('a', 254 - strlen($domain)) . $domain;
        $this->assertFalse(assertDomain($domain, false));
    }

    #[Test]
    public function testInvalidCharactersInLabel(): void
    {
        $this->assertFalse(assertDomain('exa_mple.com', false));
    }

    #[Test]
    public function testHyphenAtStartOrEndOfLabel(): void
    {
        $this->assertFalse(assertDomain('-example.com', false));
        $this->assertFalse(assertDomain('example-.com', false));
    }

    #[Test]
    public function testSingleLabelRequiresTldByDefault(): void
    {
        $this->assertFalse(assertDomain('localhost', false));
    }

    #[Test]
    public function testSingleLabelWithoutTldRequirementIsAllowed(): void
    {
        $this->assertTrue(assertDomain('localhost', true, false));
    }
}
