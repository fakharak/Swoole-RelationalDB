<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector\Bean;

use Small\Collection\Collection\Collection;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Exception\WrongFormatException;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class ConditionElement
{

    /**
     * @param ConditionElementType $type
     * @param int|float|string|mixed[]|null $value
     * @param string|null $table
     * @throws SyntaxErrorException
     */
    public function __construct(
        protected ConditionElementType $type = ConditionElementType::const,
        protected int|float|string|array|null $value = null,
        protected string|null $table = null,
    ) {

        if ($this->type == ConditionElementType::var && !is_string($this->table)) {
            throw new SyntaxErrorException('Condition element of type var must have table in constructor');
        }

        if ($this->type == ConditionElementType::var && !is_string($this->value)) {
            throw new SyntaxErrorException('Wrong format for ConditionElement value : var type must be field name');
        }

    }

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
     * @return mixed[]|float|int|string|null
     */
    public function getValue(): array|float|int|string|null
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
     * @param Record[] $records
     * @return mixed[]|float|int|string|null
     */
    public function computeValue(array $records): Collection|array|float|int|string|null
    {

        switch ($this->type)
        {

            case ConditionElementType::const:
                return $this->value;

            case ConditionElementType::var:
                if (!is_string($this->value)) {
                    throw new WrongFormatException('Condition var must be identified by a string');
                }
                return $records[$this->table]->getValue($this->value);

        }

        /** @phpstan-ignore-next-line */
        throw new \LogicException('Wrong condition element type');

    }

}