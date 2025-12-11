<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use PHPUnit\Framework\TestCase;

use oihana\commands\styles\JsonStyle;
use oihana\commands\traits\JsonStyleTrait;

use Symfony\Component\Console\Output\OutputInterface;

class JsonStyleTraitTest extends TestCase
{
    /**
     * Crée une classe factice pour tester le trait JsonStyleTrait.
     */
    private function getTraitMock(): object
    {
        return new class {
            use JsonStyleTrait;
        };
    }

    public function testGetJsonIsLazyAndCachesInstance(): void
    {
        $trait  = $this->getTraitMock();
        $output = $this->createStub(OutputInterface::class);

        $instance1 = $trait->getJson($output);
        $instance2 = $trait->getJson($output);

        $this->assertSame($instance1, $instance2, "getJson() doit retourner la même instance lors d'appels multiples.");
    }

    public function testGetJsonUsesProvidedOutput(): void
    {
        $trait  = $this->getTraitMock();
        $output = $this->createStub(OutputInterface::class);

        $jsonStyle = $trait->getJson($output);

        $this->assertSame
        (
            JsonStyle::class,
            $jsonStyle::class,
            "L'instance de JsonStyle doit être correctement initialisée avec l'Output fourni."
        );
    }
}