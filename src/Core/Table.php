<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\TableRegistry;

class Table extends \Swoole\Table
{

    /** @var Column[] */
    protected array $columns = [];

    /** @var ForeignKey[] */
    protected array $foreignKeys = [];

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
        $this->columns[$column->getName()] = $column;

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getRecord(mixed $key): Record
    {
        return new Record($this->getName(), $key, $this->get($key));
    }

    public function addForeignKey(string $name, string $toTableName, string $fromField, string $toField = '_key'): self
    {

        if (!isset($this->getColumns()[$fromField]) && $fromField != '_key') {
            throw new NotFoundException('Field \'' . $fromField . '\' not exists in table \'' . $this->getName() . '\' on foreign key creation');
        }

        $toTable = TableRegistry::getInstance()->getTable($toTableName);

        if (!isset($toTable->getColumns()[$toField]) && $toField != '_key') {
            throw new NotFoundException('Field \'' . $toField . '\' not exists in table \'' . $toTable->getName() . '\' on foreign key creation');
        }

        $foreignKey = new ForeignKey($name, $this->name, $fromField, $toTableName, $toField, ForeignKeyType::from);
        foreach ($this as $fromKey => $fromRecord) {
            foreach ($toTable as $toKey => $toRecord) {
                $fromValue = $fromField == '_key' ? $fromKey : $fromRecord[$fromField];
                $toValue = $toField == '_key' ? $toKey : $toRecord[$toField];
                if ($fromValue == $toValue) {
                    $foreignKey->addToIndex($fromValue, $toKey);
                }
            }
        }
        $this->foreignKeys[$name] = $foreignKey;

        $foreignKey = new ForeignKey($name, $toTableName, $toField, $this->name, $fromField, ForeignKeyType::to);
        foreach ($toTable as $toKey => $toRecord) {
            foreach ($this as $fromKey => $fromRecord) {
                $fromValue = $fromField == '_key' ? $fromKey : $fromRecord[$fromField];
                $toValue = $toField == '_key' ? $toKey : $toRecord[$toField];
                if ($fromValue == $toValue) {
                    $foreignKey->addToIndex($toValue, $fromKey);
                }
            }
        }
        $toTable->foreignKeys[$name] = $foreignKey;

        return $this;

    }

    /**
     * @param string $foreignKeyName
     * @param mixed $from
     * @return Record[]
     */
    public function getJoinedRecords(string $foreignKeyName, Record $from): array
    {
        return $this->foreignKeys[$foreignKeyName]->getForeignRecords($from);
    }

}