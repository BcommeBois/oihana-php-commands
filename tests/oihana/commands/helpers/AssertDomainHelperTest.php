<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function oihana\commands\helpers\assertDomain;

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
    public function testEmptyLabelReturnsFalseWhenThrowDisabled(): void
    {
        // Two consecutive dots create an empty label without exceeding 253 chars.
        $this->assertFalse(assertDomain('example..com', false));
    }

    #[Test]
    public function testTooLongLabelReturnsFalseWhenThrowDisabled(): void
    {
        // A single 64-char label (> 63) that keeps the domain under 253 chars.
        $this->assertFalse(assertDomain(str_repeat('a', 64) . '.com', false));
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

    #[Test]
    public function testTooLongDomainThrowsWhenThrowEnabled(): void
    {
        $label  = str_repeat('a', 63);
        $domain = $label . '.' . $label . '.' . $label . '.abcd';
        $domain = str_repeat('a', 254 - strlen($domain)) . $domain;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain exceeds maximum length of 253 characters.');
        assertDomain($domain);
    }

    #[Test]
    public function testEmptyLabelThrowsWhenThrowEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid label in domain: ''.");
        assertDomain('example..com');
    }

    #[Test]
    public function testTooLongLabelThrowsWhenThrowEnabled(): void
    {
        $label = str_repeat('a', 64);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid label in domain: '$label'.");
        assertDomain($label . '.com');
    }

    #[Test]
    public function testInvalidCharactersInLabelThrowsWhenThrowEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid characters in domain label: 'exa_mple'.");
        assertDomain('exa_mple.com');
    }

    #[Test]
    public function testHyphenAtStartOfLabelThrowsWhenThrowEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Label cannot start or end with a hyphen: '-example'.");
        assertDomain('-example.com');
    }

    #[Test]
    public function testSingleLabelRequiresTldThrowsWhenThrowEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Domain must include a top-level domain (e.g. '.com').");
        assertDomain('localhost');
    }
}
