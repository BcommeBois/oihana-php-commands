<?php

namespace oihana\commands\helpers;

use JsonSerializable;
use oihana\enums\Char;
use oihana\reflect\Reflection;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Format a json object in the terminal output.
 * @param SymfonyStyle $io
 * @param mixed $data
 * @param int $indent
 * @param bool $isArrayItem
 * @param array $seen
 * @param Reflection|null $reflection
 * @return void
 * @throws ReflectionException
 */
function formatJson( SymfonyStyle $io, mixed $data , int $indent = 0, bool $isArrayItem = false, array &$seen = [] , ?Reflection $reflection = null ):void
{
    if( !isset( $reflection ) )
    {
        $reflection = new Reflection() ;
    }

    if ( is_object( $data ) )
    {
        $objectId = spl_object_id( $data ) ;
        if ( isset( $seen[ $objectId ] ) )
        {
            $io->writeln('<fg=red>*"</fg=red><fg=comment> [Circular Reference]</fg=comment>');
            return;
        }
        $seen[ $objectId ] = true ;

        if ( $data instanceof JsonSerializable )
        {
            $data = $data->jsonSerialize();
        }
        else
        {

            $array = [] ;
            $properties = $reflection->properties( $data ) ;
            foreach ( $properties as $property )
            {
                /**
                 * @var ReflectionProperty $property
                 */
                $data[ $property->getName() ] = $property->getValue( $data );
            }
            $data = $array ;
        }
    }

    if ( is_array( $data ) )
    {
        $isAssociative = array_keys( $data ) !== range(0, count($data) - 1 ) ;

        if ( $isAssociative )
        {
            if ( !$isArrayItem )
            {
                $io->write(str_repeat(Char::SPACE , $indent ) . '<fg=yellow>{</fg=yellow>');
            }
        }
        else
        {
            $io->write(str_repeat(Char::SPACE, $indent) . '<fg=yellow>[</fg=yellow>');
        }

        $count = count( $data ) ;
        $i = 0;

        foreach ($data as $key => $value)
        {
            $currentIndent = $indent + 2;
            $io->write(str_repeat(Char::SPACE, $currentIndent));

            if( $isAssociative )
            {
                $io->write('<fg=blue>"' . addslashes((string) $key) . '": </fg=blue>');
            }

            formatJson( $io , $value , $currentIndent, !$isAssociative , $seen , $reflection );

            if ( ++$i < $count )
            {
                $io->writeln( Char::COMMA ) ;
            }
            else
            {
                $io->writeln(Char::EMPTY );
            }
        }

        if ($isAssociative)
        {
            if (!$isArrayItem)
            {
                $io->writeln(str_repeat(Char::SPACE , $indent ) . '<fg=yellow>}</fg=yellow>' ) ;
            }
        }
        else
        {
            $io->writeln(str_repeat(Char::SPACE, $indent) . '<fg=yellow>]</fg=yellow>');
        }
    }
    else
    {
        if ( is_string( $data ) )
        {
            $io->writeln('<fg=green>"' . addslashes($data) . '"</fg=green>');
        }
        elseif ( is_numeric( $data ) )
        {
            $io->writeln('<fg=cyan>' . $data . '</fg=cyan>');
        }
        elseif ( is_bool( $data ) )
        {
            $io->writeln('<fg=cyan>' . ($data ? 'true' : 'false') . '</fg=cyan>');
        }
        elseif ( is_null( $data ) )
        {
            $io->writeln('<fg=cyan>null</fg=cyan>');
        }
        else
        {
            $io->writeln('<fg=default>' . $data . '</fg=default>');
        }
    }

    if ( is_object( $data ) )
    {
        unset( $seen[ $objectId ] ) ;
    }
}