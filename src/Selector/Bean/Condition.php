<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

readonly class Condition
{

    public function __construct(
        protected ConditionElement $leftElement,
        protected ConditionOperator $operator,
        protected ConditionElement|null $rightElement = null,
    ) {}

    /**
     * @return ConditionElement
     */
    public function getLeftElement(): ConditionElement
    {
        return $this->leftElement;
    }

    /**
     * @return ConditionOperator
     */
    public function getOperator(): ConditionOperator
    {
        return $this->operator;
    }

    /**
     * @return ConditionElement|null
     */
    public function getRightElement(): ?ConditionElement
    {
        return $this->rightElement;
    }

    /**
     * Escape like sting to regex string
     * @param string $string
     * @return string
     */
    private function likeToRegex(string $string): string
    {
        $result = str_replace('%', '.*', $string);
        $result = str_replace('_', '.', $result);

        return $result;
    }

    /**
     * Validate condition with records
     * @param array $records
     * @return bool
     * @throws SyntaxErrorException
     */
    public function validateCondition(array $records): bool
    {

        switch ($this->operator) {

            case ConditionOperator::equal:
                return $this->leftElement->computeValue($records) == $this->rightElement->computeValue($records);
            case ConditionOperator::notEqual:
                return $this->leftElement->computeValue($records) != $this->rightElement->computeValue($records);
            case ConditionOperator::inferior:
                return $this->leftElement->computeValue($records) < $this->rightElement->computeValue($records);
            case ConditionOperator::inferiorOrEqual:
                return $this->leftElement->computeValue($records) <= $this->rightElement->computeValue($records);
            case ConditionOperator::superior:
                return $this->leftElement->computeValue($records) > $this->rightElement->computeValue($records);
            case ConditionOperator::superiorOrEqual:
                return $this->leftElement->computeValue($records) >= $this->rightElement->computeValue($records);
            case ConditionOperator::like:
                return preg_match(
                    '/^' . $this->likeToRegex($this->rightElement->computeValue($records)) . '$/',
                    $this->leftElement->computeValue($records)
                );
            case ConditionOperator::isNull:
                if ($this->rightElement !== null) {
                    throw new SyntaxErrorException('Operator \'is\' allow only null as right operator');
                }
                return $this->leftElement->computeValue($records) === null;
            case ConditionOperator::isNotNull:
                if ($this->rightElement !== null) {
                    throw new SyntaxErrorException('Operator \'is not\' allow only null as right operator');
                }
                return $this->leftElement->computeValue($records) !== null;
            case ConditionOperator::regex:
                if (!is_string($this->rightElement->computeValue($records))) {
                    throw new SyntaxErrorException('Right operator must be regex string');
                }
                return preg_match(
                    '/^' . $this->rightElement->computeValue($records) . '$/',
                    $this->leftElement->computeValue($records)
                );
            case ConditionOperator::exists:
                if ($this->rightElement !== null) {
                    throw new SyntaxErrorException('Operator \'exists\' allow only null as right operator');
                }
                if ($this->leftElement->computeValue($records) == null) {
                    return false;
                }
                return count($this->leftElement->computeValue($records)) > 0;
            case ConditionOperator::notExists:
                if ($this->rightElement !== null) {
                    throw new SyntaxErrorException('Operator \'exists\' allow only null as right operator');
                }
                if ($this->leftElement->computeValue($records) == null) {
                    return true;
                }
                return count($this->leftElement->computeValue($records)) == 0;
            case ConditionOperator::in:
                if (!is_array($this->rightElement->computeValue($records))) {
                    throw new SyntaxErrorException('Operator \'in\' allow only array as right operator');
                }
                return in_array($this->leftElement->computeValue($records), $this->rightElement->computeValue($records));
            case ConditionOperator::notIn:
                if (!is_array($this->rightElement->computeValue($records))) {
                    throw new SyntaxErrorException('Operator \'in\' allow only array as right operator');
                }
                return !in_array($this->leftElement->computeValue($records), $this->rightElement->computeValue($records));
            default:
                throw new \LogicException('Operator ' . $this->operator->name . ' is not supported yet');

        }

    }

}