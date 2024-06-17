<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\Index;

use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Exception\TableNotExists;
use Small\SwooleDb\Registry\TableRegistry;

class Index implements \JsonSerializable
{

    const KEY_MAX_SIZE = 64;

    protected Table $table;

    public function __construct(
        public readonly string $name,
        public readonly int $indexMaxSize,
        public readonly int $indexDataMaxSize,
    ) {

        try {
            $this->table = TableRegistry::getInstance()->getTable($this->name);
        } catch (TableNotExists) {
            $this->table = TableRegistry::getInstance()->createTable($this->name, $this->indexMaxSize);
            $this->createIndexTable();
        }

    }

    private function createIndexTable(): self
    {

        $this->table->addColumn(new Column('keyLeft', ColumnType::int, self::KEY_MAX_SIZE));
        $this->table->addColumn(new Column('keyRight', ColumnType::int, self::KEY_MAX_SIZE));
        $this->table->addColumn(new Column('data', ColumnType::string, $this->indexDataMaxSize));
        $this->table->addColumn(new Column('tableKeys', ColumnType::string, self::KEY_MAX_SIZE * 100));

        $this->table->create();

        return $this;

    }

    /**
     * Search value
     * @param (int|float|string|null)[] $values
     * @return string[]
     * @throws \Small\SwooleDb\Exception\IndexException
     */
    public function searchEqual(array $values): array
    {

        if ($this->table->count() === 0) {
            return [];
        }

        return (new IndexNode($this, $this->table))
            ->load(1)
            ->searchEqual($values)
        ;

    }

    /**
     * Insert a value
     * @param string $key
     * @param (int|float|string|null)[] $values
     * @return $this
     */
    public function insert(string $key, array $values): self
    {

        $root = new IndexNode($this, $this->table);
        try {
            $root
                ->load(1)
                ->insert($key, $values);
        } catch (NotFoundException) {
            $root
                ->insert($key, $values);
        }

        return $this;

    }

    /**
     * Remove a value
     * @param string $key
     * @param (int|float|string|null)[] $values
     * @return $this
     */
    public function remove(string $key, array $values): self
    {

        try {
            (new IndexNode($this, $this->table))
                ->load(1)
                ->removeKey($key, $values);
        } catch (NotFoundException) {}

        return $this;

    }

    /**
     * @param Operator $operator
     * @param (int|float|string|null)[] $values
     * @return string[]
     */
    public function getKeys(Operator $operator, array $values): array
    {

        return (new IndexNode($this, $this->table))
            ->load(1)
            ->getKeys($operator, $values);

    }


    public function jsonSerialize(): mixed
    {

        return (new IndexNode($this, $this->table))
            ->load(1)
            ->jsonSerialize();

    }

}