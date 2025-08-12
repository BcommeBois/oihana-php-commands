<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function oihana\commands\helpers\domainExists;

final class DomainExistsHelperTest extends TestCase
{
    #[Test]
    public function testInvalidDomainReturnsFalse(): void
    {
        $this->assertFalse( domainExists('invalid_domain'));
        $this->assertFalse( domainExists('')); // empty not URL-valid
        $this->assertFalse( domainExists('-.example.com')); // hyphen start invalid URL when prefixed with http://
    }

    #[Test]
    public function testWellFormedButProbablyNonExistingDomainMayReturnFalse(): void
    {
        // We cannot guarantee DNS in test environment; ensure function does not error and returns a bool.
        $result = domainExists('example.com');
        $this->assertIsBool($result);
    }
}
