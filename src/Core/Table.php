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
use Small\SwooleDb\Exception\ForbiddenActionException;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\TableRegistry;

class Table implements \Iterator
{

    protected \OpenSwoole\Table $openswooleTable;

    protected mixed $current;

    /** @var Column[] */
    protected array $columns = [];

    /** @var ForeignKey[] */
    protected array $foreignKeys = [];

    public function __construct(
        protected string $name,
        private int $maxSize,
        float $conflict_proportion = 0.2
    ) {

        $this->openswooleTable = new \OpenSwoole\Table($this->maxSize, $conflict_proportion);

    }

    public function create(): self
    {

        $this->openswooleTable->create();

        return $this;

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

        $this->openswooleTable->column($column->getName(), $column->getType()->value, $column->getSize());
        $this->columns[$column->getName()] = $column;

        if (in_array($column->getType(), [ColumnType::float, ColumnType::int])) {
            $this->openswooleTable->column($column->getName() . '::sign', ColumnType::int->value, 1);
        }

        $this->openswooleTable->column($column->getName() . '::null', ColumnType::int->value, 1);

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

        $isNull = $this->openswooleTable->get($key, $column . '::null') == 1;
        if ($isNull) {
            throw new FieldValueIsNull('Value for column ' . $column . ' is null');
        }

        if (in_array($this->getColumns()[$column]->getType(), [ColumnType::int, ColumnType::float])) {
            if ($this->openswooleTable->get($column . '::sign') == 1) {
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

        $rawResult = $this->openswooleTable->get($key);

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

        return $this->openswooleTable->set($key, $this->setMetasValues($value));

    }

    /**
     * Get a record
     * @param string $key
     * @return Record
     * @throws NotFoundException
     */
    public function getRecord(string $key): Record
    {

        if (!$this->exists($key)) {
            throw new NotFoundException('Record not found');
        }

        return new Record($this->getName(), $key, $this->get($key));

    }

    /**
     * Check if key exists
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {

        return $this->openswooleTable->exists($key);

    }

    /**
     * Add a foreign key
     * @param string $name
     * @param string $toTableName
     * @param string $fromField
     * @param string $toField
     * @return $this
     * @throws NotFoundException
     * @throws \Small\SwooleDb\Exception\TableNotExists
     */
    public function addForeignKey(string $name, string $toTableName, string $fromField, string $toField = '_key'): self
    {

        if (!isset($this->getColumns()[$fromField]) && $fromField != '_key') {
            throw new NotFoundException('Field \'' . $fromField . '\' not exists in table \'' . $this->getName() . '\' on foreign key creation');
        }

        $toTable = TableRegistry::getInstance()->getTable($toTableName);

        if (!isset($toTable->getColumns()[$toField]) && $toField != '_key') {
            throw new NotFoundException('Field \'' . $toField . '\' not exists in table \'' . $toTable->getName() . '\' on foreign key creation');
        }

        $linkFn = function (
            ForeignKey $foreignKey,
            self $fromTable,
            self $toTable,
            string $fromField,
            string $toField,
        ) {

            foreach ($fromTable as $fromKey => $fromRecord) {

                foreach ($toTable as $toKey => $toRecord) {

                    $fromValue = $fromField == '_key' ? $fromKey : $fromRecord->getValue($fromField);
                    $toValue = $toField == '_key' ? $toKey : $toRecord->getValue($toField);

                    if ($fromValue == $toValue) {
                        $foreignKey->addToIndex($fromValue, $toKey);
                    }

                }

            }

            return $foreignKey;

        };

        $foreignKey = new ForeignKey($name, $this->name, $fromField, $toTableName, $toField, ForeignKeyType::from);
        $this->foreignKeys[$name] = $linkFn($foreignKey, $this, $toTable, $fromField, $toField);

        $foreignKey = new ForeignKey($name, $toTableName, $toField, $this->name, $fromField, ForeignKeyType::to);
        $toTable->foreignKeys[$name] = $linkFn($foreignKey, $toTable, $this, $toField, $fromField);

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

    public function current(): Record
    {

        $data = $this->openswooleTable->current();

        return new Record($this->name, $this->openswooleTable->key(), $data);

    }

    public function next(): void
    {

        $this->openswooleTable->next();

    }

    public function key(): ?string
    {

        return $this->openswooleTable->key();

    }

    public function valid(): bool
    {

        return $this->openswooleTable->valid();

    }

    public function rewind(): void
    {

        $this->openswooleTable->rewind();

    }

    public function del(string $key): bool
    {

        return $this->openswooleTable->del($key);

    }

    public function destroy(): bool
    {

        if (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]['class'] != TableRegistry::class) {
            throw new ForbiddenActionException('You must use registry to destroy a table');
        }

        return $this->openswooleTable->destroy();

    }

}