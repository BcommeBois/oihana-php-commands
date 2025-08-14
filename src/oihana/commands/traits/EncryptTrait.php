<?php

namespace oihana\commands\traits;

use oihana\enums\Param;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The encrypt trait.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait EncryptTrait
{
    use PassphraseTrait ;

    /**
     * Encrypt the dumps of the database.
     * @var bool
     */
    protected bool $encrypt = true ;

    /**
     * Indicates if the dump/restore method encrypt the datas.
     * @param InputInterface $input
     * @return bool
     */
    protected function isEncrypted( InputInterface $input ):bool
    {
        return $input->getOption( Param::ENCRYPT ) || $this->encrypt ;
    }

    /**
     * Initialize the internal encrypt value.
     * @param array $init
     * @return static
     */
    public function initializeEncrypt( array $init = [] ):static
    {
        $this->encrypt = $init[ Param::ENCRYPT ] ?? $this->encrypt ;
        return $this ;
    }
}