<?php

namespace Small\SwooleDb\Core;

class Table extends \Swoole\Table
{

    /** @var Column[] */
    protected array $columns = [];

    public function __construct(private int $maxSize, float $conflict_proportion = 0.2)
    {
        parent::__construct($this->maxSize, $conflict_proportion);
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

}