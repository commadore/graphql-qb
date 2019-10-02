<?php

namespace Commadore\GraphQL\Interfaces;

interface OperationInterface extends FieldQueryInterface
{
    public function variables(array $variables): OperationInterface;
}
