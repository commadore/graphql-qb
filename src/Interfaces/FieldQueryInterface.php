<?php

namespace Commadore\GraphQL\Interfaces;

interface FieldQueryInterface
{
    public function fields(array $fields = []): FieldQueryInterface;
}
