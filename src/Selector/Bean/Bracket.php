<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Selector\Enum\BracketOperator;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class Bracket
{

    /** @var (Condition|Bracket)[] */
    protected array $conditions = [];
    /** @var BracketOperator[] */
    protected array $operators = [];

    /**
     * @return Bracket[]|Condition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return BracketOperator[]
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * Add condition to bracket as first condition
     * @param Condition $condition
     * @return $this
     * @throws SyntaxErrorException
     */
    public function firstCondition(Condition $condition): self
    {
        if (array_key_exists(0, $this->conditions)) {
            throw new SyntaxErrorException('Bracket as already hav a first condition');
        }

        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * Add bracket to bracket as first condition
     * @return self
     * @throws SyntaxErrorException
     */
    public function firstBracket(Bracket $bracket = null): self
    {

        if (array_key_exists(0, $this->conditions)) {
            throw new SyntaxErrorException('Bracket as already hav a first condition');
        }

        if ($bracket === null) {
            $bracket = new Bracket();
        }

        $this->conditions[] = $bracket;

        return $bracket;

    }

    /**
     * Add and condition
     * @param Condition $condition
     * @return $this
     */
    public function andCondition(Condition $condition): self
    {

        $this->operators[] = BracketOperator::and;
        $this->conditions[] = $condition;

        return $this;

    }

    /**
     * Add and bracket
     * @return Bracket
     */
    public function andBracket(Bracket $bracket = null): self
    {

        $this->operators[] = BracketOperator::and;

        if ($bracket === null) {
            $bracket = new Bracket();
        }

        $this->conditions[] = $bracket;

        return $bracket;

    }

    /**
     * Add or condition
     * @param Condition $condition
     * @return $this
     */
    public function orCondition(Condition $condition): self
    {

        $this->operators[] = BracketOperator::or;
        $this->conditions[] = $condition;

        return $this;

    }

    /**
     * Add or bracket
     * @return Bracket
     */
    public function orBracket(Bracket $bracket = null): self
    {

        $this->operators[] = BracketOperator::or;

        if ($bracket === null) {
            $bracket = new Bracket();
        }

        $this->conditions[] = $bracket;
        
        return $bracket;

    }

    /**
     * Validate conditions in bracket
     * @param RecordCollection $records
     * @return bool
     * @throws SyntaxErrorException
     */
    public function validateBracket(RecordCollection $records): bool
    {

        if (count($this->conditions) == 0) {
            return true;
        }

        /** @var bool[] $conditionResults */
        $conditionResults = [];
        foreach ($this->conditions as $condition) {
            if ($condition instanceof Condition) {
                $conditionResults[] = $condition->validateCondition($records);
            } elseif ($condition instanceof Bracket) {
                $conditionResults[] = $condition->validateBracket($records);
            } else {
                throw new \LogicException('Unknown condition class');
            }
        }

        return $this->chainOperations($conditionResults);

    }

    /**
     * Compute condition result
     * @param bool[] $conditionResults
     * @return bool
     */
    private function chainOperations(array $conditionResults): bool
    {

        $result = $conditionResults[0];
        for ($i = 1; $i < count($conditionResults); $i++) {
            if ((!$conditionResults[$i] || !$result) && $this->operators[$i - 1] == BracketOperator::and) {
                return false;
            } elseif ($this->operators[$i - 1] == BracketOperator::and) {
                $result = true;
            /** @phpstan-ignore-next-line */
            } elseif (($result || $conditionResults[$i]) && $this->operators[$i - 1] == BracketOperator::or) {
                return true;
            } elseif ($this->operators[$i - 1] == BracketOperator::or) {
                $result = false;
            }
        }

        return $result;

    }

}