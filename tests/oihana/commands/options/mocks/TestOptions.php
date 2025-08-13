<?php

namespace tests\oihana\commands\options\mocks;

use oihana\commands\options\Options;

class TestOptions extends Options
{
    public ?string $host   = null;
    public ?int    $port   = null;
    public array   $flags  = [];
    public ?bool   $debug  = null;
}