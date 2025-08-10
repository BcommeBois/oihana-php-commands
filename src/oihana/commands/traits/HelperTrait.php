<?php

namespace oihana\commands\traits;

use oihana\commands\enums\CommandHelper;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * The Helper trait.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait HelperTrait
{
    /**
     * The question helper reference of the command.
     * @var ?QuestionHelper
     */
    public ?QuestionHelper $questionHelper = null ;

    /**
     * Returns the QuestionHelper reference of the command.
     * @return QuestionHelper
     */
    public function getQuestionHelper() : QuestionHelper
    {
        if( !isset( $this->questionHelper ) )
        {
            $helper = $this->getHelper(CommandHelper::QUESTION )  ;
            if ( !$helper instanceof QuestionHelper)
            {
                throw new LogicException('The "question" helper is not an instance of QuestionHelper.' ) ;
            }
            $this->questionHelper = $helper ;
        }
        return $this->questionHelper ;
    }
}