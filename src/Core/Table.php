<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Exception\FieldValueIsNull;
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

        if (in_array($column->getType(), [ColumnType::float, ColumnType::int])) {
            $this->column($column->getName() . '::sign', ColumnType::int->value, 1);
        }

        $this->column($column->getName() . '::null', ColumnType::int->value, 1);

        return $this;

    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    protected function formatValue(string $key, string $column, mixed $value): string|int|float|bool
    {

        $isNull = parent::get($key, $column . '::null') == 1;
        if ($isNull) {
            throw new FieldValueIsNull('Value for column ' . $column . ' is null');
        }

        if (in_array($this->getColumns()[$column]->getType(), [ColumnType::int, ColumnType::float])) {
            if (parent::get($column . '::sign') == 1) {
                $value = -$value;
            }
        }

        return $value;

    }

    /**
     * @param array $rawRecord
     * @return array
     */
    protected function setMetasValues(array $rawRecord): array
    {

        $array = $rawRecord;
        foreach ($rawRecord as $column => $value) {
            if ($value === null) {
                $array[$column . '::null'] = 1;
                $array[$column] = 0;
            } else {
                $array[$column . '::null'] = 0;
            }

            if ($value !== null && in_array($this->getColumns()[$column]->getType(), [ColumnType::int, ColumnType::float])) {
                $array[$column . '::sign'] = $value < 0 ? 1 : 0;
                $value = abs($value);
            }

            switch($this->getColumns()[$column]->getType()) {
                case ColumnType::int:
                    $array[$column] = (int)$value;
                    break;
                case ColumnType::float:
                    $array[$column] = (float)$value;
                    break;
                case ColumnType::string:
                    $array[$column] = (string)$value;
                    break;
            }
        }

        return $array;

    }

    public function get(string $key, string $column = ''): array|string|int|float|bool
    {

        $rawResult = parent::get($key);

        if ($column !== '') {
            return $this->formatValue($key, $column, $rawResult);
        }

        $result = [];
        foreach ($rawResult as $column => $item) {

            if (
                (strstr($column, '::null') === false) &&
                (strstr($column, '::sign') === false)
            ) {
                try {
                    $result[$column] = $this->formatValue($key, $column, $item);
                } catch (FieldValueIsNull) {
                    $result[$column] = null;
                }
            }
        }

        return $result;

    }

    public function set(string $key, array $value): bool
    {

        $result = [];
        foreach ($value as $field => $item) {
            $result[$field] = $item === null ? $this->getColumns()[$field]->getNullValue() : $item;
        }

        return parent::set($key, $this->setMetasValues($value));

    }

    /**
     * Get a record
     * @param mixed $key
     * @return Record
     */
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