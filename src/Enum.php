<?php

namespace Commadore\GraphQL;

class Enum
{
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}