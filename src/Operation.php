<?php

namespace Commadore\GraphQL;

use Commadore\GraphQL\Exceptions\InvalidTypeException;
use Commadore\GraphQL\Interfaces\FieldQueryInterface;
use Commadore\GraphQL\Interfaces\OperationInterface;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;

class Operation implements OperationInterface
{
    private $operationName;
    private $operationType;
    private $fields;
    private $variables;


    /**
     * Operation constructor.
     * @param string $operationType
     * @param string $operationName
     * @param array $variables
     * @param array $fields
     * @throws InvalidTypeException
     */
    public function __construct(string $operationType, string $operationName, array $variables=[], array $fields=[])
    {
        $this->checkType($operationType);
        $this->operationType = $operationType;
        $this->operationName = $operationName;
        $this->variables = $variables;
        $this->fields = $fields;
    }

    /**
     * @param array $fields
     *
     * @return self
     */
    public function fields(array $fields = []): FieldQueryInterface
    {
        foreach ($fields as $fieldAlias => $field) {
            if (\is_string($field)) {
                if (\is_string($fieldAlias)) {
                    $this->fields[$fieldAlias] = $field;
                } else {
                    $this->fields[$field] = $field;
                }
            }

            if ($field instanceof AbstractQuery) {
                $field->isOperation = false;
                $this->fields[$fieldAlias] = $field;
            }
        }

        ksort($this->fields);

        return $this;
    }

    /**
     * @param array $variables
     *
     * @return self
     */
    public function variables(array $variables = []): OperationInterface
    {
        foreach ($variables as $variableName => $variableType) {
            $this->variables[(string) $variableName] = (string) $variableType;
        }

        return $this;
    }

    public function __toString()
    {
        $query = sprintf('%s { %s }',
            $this->printQuery($this->operationName, $this->variables),
            $this->printFields($this->fields));
        $query = Printer::doPrint(Parser::parse((string) $query));

        return $query;
    }

    /**
     * @param string $type
     * @throws InvalidTypeException
     */
    private function checkType(string $type)
    {
        if (($type === Mutation::KEYWORD) || ($type === Query::KEYWORD))
        {
            return;
        }
        throw new InvalidTypeException($type . ' is not a valid Operation.');
    }
    /**
     * @param $operationName
     * @param $variables
     *
     * @return string
     */
    private function printQuery($operationName, $variables): string
    {
        return sprintf('%s %s %s', $this->operationType, $operationName, $this->printVariables($variables));
    }


    /**
     * @param array $value
     *
     * @return string
     */
    private function printVariables(array $value): string
    {
        if (!\count($value)) {
            return '';
        }

        $variables = [];

        foreach ($value as $variableName => $variableType) {
            $variables[] = sprintf('%s: %s', $variableName, $variableType);
        }

        return sprintf('(%s)', implode(', ', $variables));
    }

    /**
     * @param array $value
     * @param array $skipIf
     * @param array $includeIf
     *
     * @return string
     */
    private function printFields(array $value, array $skipIf = [], array $includeIf = []): string
    {
        $fields = [];

        foreach ($value as $fieldAlias => $field) {
            $directive = '';

            if (\is_string($field)) {
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

            if ($field instanceof AbstractQuery) {
                $field->isOperation = false;
                if(empty($field->type))
                {
                    $field->type = $fieldAlias;
                }
                $fields[] = sprintf('%s: %s', $fieldAlias, $field->__toString());
            }
        }

        return implode(', ', $fields);
    }
}
