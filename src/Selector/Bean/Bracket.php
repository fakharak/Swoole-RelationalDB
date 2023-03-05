<?php

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Enum\BracketOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class Bracket
{

    /** @var (Condition|Bracket)[] */
    protected array $conditions = [];
    /** @var BracketOperator[] */
    protected array $operators = [];

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
     * @param Bracket $bracket
     * @return $this
     * @throws SyntaxErrorException
     */
    public function firstBracket(Bracket $bracket): self
    {
        if (array_key_exists(0, $this->conditions)) {
            throw new SyntaxErrorException('Bracket as already hav a first condition');
        }

        $this->conditions[] = $bracket;

        return $this;
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
     * @param Bracket $bracket
     * @return $this
     */
    public function andBracket(Bracket $bracket)
    {
        $this->operators[] = BracketOperator::and;
        $this->conditions[] = $bracket;

        return $this;
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
     * @param Bracket $bracket
     * @return $this
     */
    public function orBracket(Bracket $bracket): self
    {
        $this->operators[] = BracketOperator::or;
        $this->conditions[] = $bracket;

        return $this;
    }

    /**
     * Validate conditions in bracket
     * @param array $records
     * @return bool
     * @throws SyntaxErrorException
     */
    public function validateBracket(array $records): bool
    {
        /** @var bool[] $conditionResults */
        $conditionResults = [];
        foreach ($this->conditions as $condition) {
            if ($condition instanceof Condition) {
                    $conditionResults[] = $condition->validateCondition(
                        $records[$condition->getLeftElement()->getTable()],
                        $records[$condition->getRightElement()->getTable()]
                    );
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
     * @param array $conditionResults
     * @return bool
     */
    private function chainOperations(array $conditionResults): bool
    {
        foreach ($conditionResults as $key => $conditionResult) {
            if (!$conditionResult && $this->operators[$key] == BracketOperator::and) {
                return false;
            }
            if ($conditionResult && $this->operators[$key] == BracketOperator::or) {
                return true;
            }
        }

        return true;
    }

}