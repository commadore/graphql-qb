<?php

namespace Commadore\GraphQL\Interfaces;

use Commadore\GraphQL\Fragment;

interface QueryInterface extends FieldQueryInterface
{
    public function arguments(array $args = []): QueryInterface;

    public function removeFields(array $fields = []): QueryInterface;

    public function skipIf(array $values = []): QueryInterface;

    public function includeIf(array $values = []): QueryInterface;

    public function addFragment(Fragment $fragment): QueryInterface;
}
