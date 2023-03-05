<?php

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class Condition
{

    public function __construct(
        protected ConditionElement $leftElement,
        protected ConditionOperator $operator,
        protected ConditionElement|null $rightElement,
    ) {}

    /**
     * @return ConditionElement
     */
    public function getLeftElement(): ConditionElement
    {
        return $this->leftElement;
    }

    /**
     * @param ConditionElement $leftElement
     * @return Condition
     */
    public function setLeftElement(ConditionElement $leftElement): Condition
    {
        $this->leftElement = $leftElement;
        return $this;
    }

    /**
     * @return ConditionOperator
     */
    public function getOperator(): ConditionOperator
    {
        return $this->operator;
    }

    /**
     * @param ConditionOperator $operator
     * @return Condition
     */
    public function setOperator(ConditionOperator $operator): Condition
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * @return ConditionElement|null
     */
    public function getRightElement(): ?ConditionElement
    {
        return $this->rightElement;
    }

    /**
     * @param ConditionElement|null $rightElement
     * @return Condition
     */
    public function setRightElement(?ConditionElement $rightElement): Condition
    {
        $this->rightElement = $rightElement;
        return $this;
    }

    /**
     * Escape like sting to regex string
     * @param string $string
     * @return string
     */
    private function likeToRegex(string $string): string
    {
        $result = str_replace('%', '.*', $this->rightElement->computeValue($string));
        $result = str_replace('_', '.', $this->rightElement->computeValue($result));

        return $result;
    }

    /**
     * Validate condition with records
     * @param array $leftRecord
     * @param array|null $rightRecord
     * @return bool
     * @throws SyntaxErrorException
     */
    public function validateCondition(array $leftRecord, array|null $rightRecord): bool
    {

        switch ($this->operator) {

            case ConditionOperator::equal:
                return $this->leftElement->computeValue($leftRecord) == $this->rightEledment->computeValue($rightRecord);
            case ConditionOperator::inferior:
                return $this->leftElement->computeValue($leftRecord) < $this->rightElement->computeValue($rightRecord);
            case ConditionOperator::inferiorOrEqual:
                return $this->leftElement->computeValue($leftRecord) <= $this->rightElement->computeValue($rightRecord);
            case ConditionOperator::superior:
                return $this->leftElement->computeValue($leftRecord) > $this->rightElement->computeValue($rightRecord);
            case ConditionOperator::superiorOrEqual:
                return $this->leftElement->computeValue($leftRecord) >= $this->rightElement->computeValue($rightRecord);
            case ConditionOperator::like:
                return preg_match(
                    '/^' . $this->likeToRegex($this->rightElement->computeValue($rightRecord)) . '$/',
                    $this->leftElement->computeValue($leftRecord)
                );
            case ConditionOperator::is:
                if ($this->rightElement != null) {
                    throw new SyntaxErrorException('Operator \'is\' allow only null as right operator');
                }
                return $this->leftElement->computeValue($leftRecord) === null;
            case ConditionOperator::isNot:
                if ($this->rightElement != null) {
                    throw new SyntaxErrorException('Operator \'is not\' allow only null as right operator');
                }
                return $this->leftElement->computeValue($leftRecord) !== null;
            default:
                throw new SyntaxErrorException('Operator ' . $this->operator->name . ' is not supported yet');

        }

    }

}