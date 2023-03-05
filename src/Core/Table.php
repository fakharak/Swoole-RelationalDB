<?php
/*
 *  This file is a part of small-env
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

class Table extends \Swoole\Table
{

    /** @var Column[] */
    protected array $columns = [];

    public function __construct(protected string $name, private int $maxSize, float $conflict_proportion = 0.2)
    {
        parent::__construct($this->maxSize, $conflict_proportion);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get max size
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function addColumn(Column $column): self
    {
        $this->column($column->getName(), $column->getType()->value, $column->getSize());
        $this->columns[] = $column;

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function set(string $key, array $value): self
    {
        parent::set($key, $value);

        return $this;
    }

    public function getRecord(mixed $key): Record
    {
        return new Record($this->getName(), $key, $this->get($key));
    }

}