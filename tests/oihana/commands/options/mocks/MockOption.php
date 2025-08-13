<?php

namespace tests\oihana\commands\options\mocks;

use oihana\commands\options\Option;

class MockOption extends Option
{
    public static function getCommandOption(string $option): string
    {
        return str_replace('_', '-', $option);
    }
}