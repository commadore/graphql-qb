<?php

namespace Commadore\GraphQL\Interfaces;

use Commadore\GraphQL\Fragment;

interface OperationInterface extends FieldQueryInterface
{
    public function variables(array $variables): OperationInterface;

    public function addFragment(Fragment $fragment): OperationInterface;
}
