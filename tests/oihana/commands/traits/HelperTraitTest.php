<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\enums\CommandHelper;
use oihana\commands\traits\HelperTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * Unit tests for HelperTrait
 */
class HelperTraitTest extends TestCase
{
    /**
     * Builds a consumer of HelperTrait whose getHelper() returns the given value.
     */
    private function fixture( mixed $helper ): object
    {
        return new class( $helper )
        {
            use HelperTrait;

            public array  $getHelperCalledWith = [] ;
            private mixed $helper ;

            public function __construct( mixed $helper )
            {
                $this->helper = $helper ;
            }

            public function getHelper( string $name ): mixed
            {
                $this->getHelperCalledWith[] = $name ;
                return $this->helper ;
            }
        } ;
    }

    public function testReturnsQuestionHelperFromGetHelper(): void
    {
        $questionHelper = new QuestionHelper() ;
        $fixture        = $this->fixture( $questionHelper ) ;

        $result = $fixture->getQuestionHelper() ;

        $this->assertSame( $questionHelper , $result ) ;
        $this->assertSame( [ CommandHelper::QUESTION ] , $fixture->getHelperCalledWith ) ;
    }

    public function testCachesResolvedQuestionHelper(): void
    {
        $fixture = $this->fixture( new QuestionHelper() ) ;

        $first  = $fixture->getQuestionHelper() ;
        $second = $fixture->getQuestionHelper() ;

        $this->assertSame( $first , $second ) ;
        $this->assertSame( [ CommandHelper::QUESTION ] , $fixture->getHelperCalledWith ) ; // resolved only once
    }

    public function testThrowsWhenHelperIsNotQuestionHelper(): void
    {
        $fixture = $this->fixture( 'not-a-helper' ) ;

        $this->expectException( LogicException::class ) ;
        $this->expectExceptionMessage( 'The "question" helper is not an instance of QuestionHelper.' ) ;

        $fixture->getQuestionHelper() ;
    }
}
