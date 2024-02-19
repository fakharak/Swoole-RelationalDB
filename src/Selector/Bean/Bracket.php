<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\Record;
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
     * @return IndexFilter[][]
     */
    public function getOptimisations(): array
    {

        $indexFilters = [];

        $broken = false;
        $keyToFilter = [];
        foreach ($this->operators as $key => $operator) {

            if ($operator == BracketOperator::and) {

                if ($this->conditions[$key] instanceof Condition) {
                    $keyToFilter[] = $key;
                }

            } else {
                $broken = true;
                break;
            }

        }

        if (!$broken) {
            if (isset($key) && $this->conditions[$key + 1] instanceof Condition) {
                $keyToFilter[] = $key + 1;
            }
        }

        $createFilter = function (string $table, int $key, string $field, mixed $value): IndexFilter|null {

            if (!$this->conditions[$key] instanceof Condition) {
                throw new \LogicException('$key don\'t point on ' . Condition::class);
            }

            $operator = match ($this->conditions[$key]->getOperator()) {
                ConditionOperator::equal => Operator::equal,
                ConditionOperator::inferior => Operator::inferior,
                ConditionOperator::inferiorOrEqual => Operator::inferiorOrEqual,
                ConditionOperator::superior => Operator::superior,
                ConditionOperator::superiorOrEqual => Operator::superiorOrEqual,
                default => null,
            };

            if ($operator === null) {
                return null;
            }

            if (!TableRegistry::getInstance()->getTable($table)->hasIndex($field)) {
                return null;
            }

            $filter = new IndexFilter(
                $operator,
                $field,
                $value,
            );

            return $filter;

        };

        foreach ($keyToFilter as $key) {

            if ($this->conditions[$key] instanceof Condition) {

                if ($this->conditions[$key]->getLeftElement()->getType() == ConditionElementType::var) {
                    if ($this->conditions[$key]->getRightElement()?->getType() == ConditionElementType::const) {

                        $table = $this->conditions[$key]->getLeftElement()->getTable() ?? '';
                        $field = $this->conditions[$key]->getLeftElement()->getValue();
                        $value = $this->conditions[$key]->getRightElement()?->getValue();

                        if (!is_string($field)) {
                            throw new SyntaxErrorException('Field must be string');
                        }

                        $filter = $createFilter($table, $key, $field, $value);
                        if ($filter !== null) {
                            $indexFilters[$table][] = $filter;
                        }

                    }
                }

                if ($this->conditions[$key]->getRightElement()?->getType() == ConditionElementType::var) {
                    if ($this->conditions[$key]->getLeftElement()->getType() == ConditionElementType::const) {

                        $table = $this->conditions[$key]->getRightElement()?->getTable() ?? '';
                        $field = $this->conditions[$key]->getRightElement()?->getValue();
                        $value = $this->conditions[$key]->getLeftElement()->getValue();

                        if (!is_string($field)) {
                            throw new SyntaxErrorException('Field must be string');
                        }

                        $filter = $createFilter($table, $key, $field, $value);
                        if ($filter !== null) {
                            $indexFilters[$table][] = $filter;
                        }

                    }
                }

            }

        }

        return $indexFilters;

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
    public function firstBracket(): self
    {

        if (array_key_exists(0, $this->conditions)) {
            throw new SyntaxErrorException('Bracket as already hav a first condition');
        }

        $this->conditions[] = $bracket = new Bracket();

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
    public function andBracket(): self
    {

        $this->operators[] = BracketOperator::and;
        $this->conditions[] = $bracket = new Bracket();

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
    public function orBracket(): self
    {

        $this->operators[] = BracketOperator::or;
        $this->conditions[] = $bracket = new Bracket();

        return $bracket;

    }

    /**
     * Validate conditions in bracket
     * @param Record[] $records
     * @return bool
     * @throws SyntaxErrorException
     */
    public function validateBracket(array $records): bool
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