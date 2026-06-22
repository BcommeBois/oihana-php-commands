<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\traits\InflectorTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

/**
 * Unit tests for InflectorTrait
 */
class InflectorTraitTest extends TestCase
{
    private object $trait;

    protected function setUp(): void
    {
        $this->trait = new class
        {
            use InflectorTrait;

            public function init
            (
                array               $init             = [] ,
                ?ContainerInterface $container        = null ,
                ?InflectorInterface $defaultInflector = null
            )
            :static
            {
                return $this->initializeInflector( $init , $container , $defaultInflector ) ;
            }
        };
    }

    public function testResolvesInflectorFromContainerService(): void
    {
        $custom = $this->createStub( InflectorInterface::class ) ;

        $container = $this->createMock( ContainerInterface::class ) ;
        $container->method( 'has' )->with( 'my.inflector' )->willReturn( true ) ;
        $container->method( 'get' )->with( 'my.inflector' )->willReturn( $custom ) ;

        $result = $this->trait->init( [ 'inflector' => 'my.inflector' ] , $container ) ;

        $this->assertSame( $this->trait , $result ) ;
        $this->assertSame( $custom , $this->trait->inflector ) ;
    }

    public function testUsesDirectInflectorInstance(): void
    {
        $custom = $this->createStub( InflectorInterface::class ) ;

        $this->trait->init( [ 'inflector' => $custom ] ) ;

        $this->assertSame( $custom , $this->trait->inflector ) ;
    }

    public function testFallsBackToProvidedDefaultInflector(): void
    {
        $default = $this->createStub( InflectorInterface::class ) ;

        $this->trait->init( [] , null , $default ) ;

        $this->assertSame( $default , $this->trait->inflector ) ;
    }

    public function testFallsBackToEnglishInflectorWhenNothingProvided(): void
    {
        $this->trait->init() ;

        $this->assertInstanceOf( EnglishInflector::class , $this->trait->inflector ) ;
    }

    public function testEmptyStringServiceFallsBackToDefault(): void
    {
        $default = $this->createStub( InflectorInterface::class ) ;

        $this->trait->init( [ 'inflector' => '' ] , null , $default ) ;

        $this->assertSame( $default , $this->trait->inflector ) ;
    }

    public function testStringServiceNotInContainerFallsBackToEnglishInflector(): void
    {
        $container = $this->createMock( ContainerInterface::class ) ;
        $container->method( 'has' )->with( 'missing.inflector' )->willReturn( false ) ;

        $this->trait->init( [ 'inflector' => 'missing.inflector' ] , $container ) ;

        $this->assertInstanceOf( EnglishInflector::class , $this->trait->inflector ) ;
    }

    public function testStringServiceWithoutContainerFallsBackToEnglishInflector(): void
    {
        $this->trait->init( [ 'inflector' => 'some.service' ] , null ) ;

        $this->assertInstanceOf( EnglishInflector::class , $this->trait->inflector ) ;
    }
}
