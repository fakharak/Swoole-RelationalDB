<?php

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Selector\Enum\ConditionElementType;

class ConditionElement
{

    public function __construct(
        protected ConditionElementType $type,
        protected string|null $table,
        protected int|float|string $value,
    ) {}

    /**
     * @return ConditionElementType
     */
    public function getType(): ConditionElementType
    {
        return $this->type;
    }

    /**
     * @param ConditionElementType $type
     * @return ConditionElement
     */
    public function setType(ConditionElementType $type): ConditionElement
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * @param string|null $table
     * @return ConditionElement
     */
    public function setTable(?string $table): ConditionElement
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return float|int|string
     */
    public function getValue(): float|int|string
    {
        return $this->value;
    }

    /**
     * @param float|int|string $value
     * @return ConditionElement
     */
    public function setValue(float|int|string $value): ConditionElement
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Compute value for a record
     * @param array $record
     * @return float|int|string
     */
    public function computeValue(array $record): float|int|string
    {

        switch ($this->type)
        {

            case ConditionElementType::const:
                return $this->value;

            case ConditionElementType::var:
                return $record[$this->value];

        }

        throw new \LogicException('Wrong condition element type');

    }

}