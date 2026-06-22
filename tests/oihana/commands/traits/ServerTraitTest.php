<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\options\ServerOptions;
use oihana\commands\traits\ServerTrait;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ServerTrait
 */
class ServerTraitTest extends TestCase
{
    private object $trait ;

    protected function setUp(): void
    {
        $this->trait = new class
        {
            use ServerTrait;

            public function init( array $init = [] ): static
            {
                return $this->initializeServerOptions( $init ) ;
            }
        } ;
    }

    public function testInitializesFromServerKey(): void
    {
        $result = $this->trait->init( [ ServerOptions::SERVER => [ 'host' => 'example.com' ] ] ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertInstanceOf( ServerOptions::class , $this->trait->serverOptions ) ;
    }

    public function testInitializesFromFullArrayWhenNoServerKey(): void
    {
        $result = $this->trait->init( [ 'host' => 'example.com' ] ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertInstanceOf( ServerOptions::class , $this->trait->serverOptions ) ;
    }
}
