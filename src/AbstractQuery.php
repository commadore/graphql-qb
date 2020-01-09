<?php

namespace Commadore\GraphQL;

use Commadore\GraphQL\Interfaces\FieldQueryInterface;
use Commadore\GraphQL\Interfaces\QueryInterface;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use function is_bool;
use function is_float;
use function is_string;

abstract class AbstractQuery implements QueryInterface
{
    abstract function getKeyword(): string;
    abstract function getPrefix(): string;

    /**
     * @var string
     */
    public $operationName;

    /**
     * @var array
     */
    public $variables = [];

    /**
     * @var bool
     */
    public $isOperation = true;

    /**
     * @var array
     */
    public $type = [];

    /**
     * @var array
     */
    public $args = [];

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var array
     */
    public $skipIf = [];

    /**
     * @var array
     */
    public $includeIf = [];

    /**
     * @var array
     */
    public $fragments = [];

    /**
     * AbstractQuery constructor.
     * @param string|null $type
     * @param array $args
     * @param array $fields
     */
    public function __construct(?string $type = null, array $args = [], array $fields = [])
    {
        $this->type = $type;
        $this->arguments($args);
        $this->fields($fields);
    }

    /**
     * @param string $operationName
     *
     * @return QueryInterface
     */
    public function operationName(string $operationName): QueryInterface
    {
        $this->operationName = $operationName;

        return $this;
    }


    /**
     * @param array $args
     *
     * @return self
     */
    public function arguments(array $args = []): QueryInterface
    {
        foreach ($args as $name => $value) {
            $this->args[$name] = $value;
        }

        ksort($this->args);

        return $this;
    }


    /**
     * @param array $value
     *
     * @return string
     */
    private function printArgs(array $value): string
    {
        if (!count($value)) {
            return '';
        }

        $args = [];
        foreach ($value as $argName => $argValue) {
            if (is_string($argValue) && '$' !== $argValue[0]) {
                $argValue = sprintf('"%s"', $argValue);
            }

            if (is_bool($argValue) || is_float($argValue)) {
                $argValue = var_export($argValue, true);
            }

            $args[] = sprintf('%s: %s', $argName, $argValue);
        }

        return sprintf('(%s)', implode(', ', $args));
    }

    /**
     * @param array $fields
     *
     * @return self
     */
    public function fields(array $fields = []): FieldQueryInterface
    {
        foreach ($fields as $fieldAlias => $field) {
            if (is_string($field)) {
                if (is_string($fieldAlias)) {
                    $this->fields[$fieldAlias] = $field;
                } else {
                    $this->fields[$field] = $field;
                }
            }

            if ($field instanceof self) {
                $field->isOperation = false;
                $this->fields[$fieldAlias] = $field;
            }
        }

        ksort($this->fields);

        return $this;
    }

    /**
     * @param array $fields
     *
     * @return self
     */
    public function removeFields(array $fields = []): QueryInterface
    {
        foreach ($fields as $field) {
            unset($this->fields[$field]);
        }

        return $this;
    }

    /**
     * @param array $value
     * @param array $skipIf
     * @param array $includeIf
     *
     * @return string
     */
    private function printFields(array $value): string
    {
        $skipIf = $this->skipIf;
        $includeIf = $this->includeIf;
        $fields = [];

        foreach ($value as $fieldAlias => $field) {
            $directive = '';

            if (is_string($field)) {
                if ($fieldAlias !== $field) {
                    if (array_key_exists($fieldAlias, $skipIf)) {
                        $directive = sprintf('@skip(if: %s)', $skipIf[$fieldAlias]);
                    } elseif (array_key_exists($fieldAlias, $includeIf)) {
                        $directive = sprintf('@include(if: %s)', $includeIf[$fieldAlias]);
                    }

                    $fields[] = sprintf('%s: %s %s', $fieldAlias, $field, $directive);
                } else {
                    if (array_key_exists($field, $skipIf)) {
                        $directive = sprintf('@skip(if: %s)', $skipIf[$field]);
                    } elseif (array_key_exists($field, $includeIf)) {
                        $directive = sprintf('@include(if: %s)', $includeIf[$field]);
                    }

                    $fields[] = sprintf('%s %s', $field, $directive);
                }
            }

            if ($field instanceof self) {
                $field->isOperation = false;
                if(empty($field->type))
                {
                    $field->type = $fieldAlias;
                }
                if(is_string($fieldAlias)) {
                    $fields[] = sprintf('%s: %s', $fieldAlias, $field->__toString());
                } else {
                    $fields[] = sprintf('%s', $field->__toString());
                }
            }
        }

        return implode(', ', $fields);
    }

    /**
     * @param array $values
     *
     * @return self
     */
    public function skipIf(array $values = []): QueryInterface
    {
        foreach ($values as $field => $argument) {
            $this->skipIf[$field] = $argument;
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return self
     */
    public function includeIf(array $values = []): QueryInterface
    {
        foreach ($values as $field => $argument) {
            $this->includeIf[$field] = $argument;
        }

        return $this;
    }

    public function __toString()
    {
        if(empty($this->fields))
        {
            $query = sprintf('%s %s',
                $this->printType($this->type),
                $this->printArgs($this->args)
            );
        }
        else
        {
            $query = sprintf('%s %s { %s }',
                $this->printType($this->type),
                $this->printArgs($this->args),
                $this->printFields($this->fields)
            );
        }
        return $query;
    }


    private function printType($value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (\is_array($value) && \count($value)) {
            foreach ($value as $alias => $type) {
                return sprintf('%s: %s', $alias, $type);
            }
        }

        return null;
    }
}
