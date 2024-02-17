<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

if (!\class_exists('\OpenSwoole\Table')) {
    \class_alias('\Swoole\Table', '\OpenSwoole\Table');
}

use Small\Collection\Collection\Collection;
use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\Index\ForeignKey;
use Small\SwooleDb\Core\Index\Index;
use Small\SwooleDb\Exception\FieldValueIsNull;
use Small\SwooleDb\Exception\ForbiddenActionException;
use Small\SwooleDb\Exception\IndexException;
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

    /** @var Index[] */
    protected array $indexes = [];

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

        $this->openswooleTable->column($column->getName(), $column->getType()->value, $column->getSize() ?? 0);
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

    protected function formatValue(int|string $key, string $column, mixed $value): mixed
    {

        $isNull = $this->openswooleTable->get((string)$key, $column . '::null') == 1;
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
     * @param mixed[] $rawRecord
     * @return mixed[]
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

                if (!is_int($value) && !is_float($value)) {
                    throw new \LogicException('Impossible value for type');
                }

                $array[$column . '::sign'] = $value < 0 ? 1 : 0;
                $value = abs($value);
            }

            switch($this->getColumns()[$column]->getType()) {
                case ColumnType::int:
                    if (!is_int($value)) {
                        throw new \LogicException('Impossible value for type');
                    }
                    $array[$column] = (int)$value;
                    break;
                case ColumnType::float:
                    if (!is_int($value) && !is_float($value)) {
                        throw new \LogicException('Impossible value for type');
                    }
                    $array[$column] = (float)$value;
                    break;
                case ColumnType::string:
                    if (!is_string($value)) {
                        throw new \LogicException('Impossible value for type');
                    }
                    $array[$column] = $value;
                    break;
            }
        }

        return $array;

    }

    /**
     * @param int|string $key
     * @param string $column
     * @return mixed
     * @throws FieldValueIsNull
     */
    public function get(int|string $key, string $column = ''): mixed
    {

        $rawResult = $this->openswooleTable->get((string)$key);

        if ($column !== '') {
            return $this->formatValue($key, $column, $rawResult);
        }

        if (!is_array($rawResult)) {
            throw new \LogicException('rawResult must be array at this point');
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

    /**
     * @param string $key
     * @param mixed[] $value
     * @return bool
     */
    public function set(string $key, array $value): bool
    {

        $result = [];
        foreach ($value as $field => $item) {
            $result[$field] = $item === null ? $this->getColumns()[$field]->getNullValue() : $item;
        }

        foreach ($this->indexes as $fieldsString => $index) {

            $values = [];
            foreach (explode('|', $fieldsString) as $field) {
                $values[] = $value[$field];
            }

            // TODO remove key
            $index->insert($key, $values);

        }

        return $this->openswooleTable->set($key, $this->setMetasValues($result));

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

        if (!is_array($array = $this->get((string)$key))) {
            throw new \LogicException('$array must be array at this point');
        }

        return new Record($this->getName(), $key, $array);

    }

    /**
     * Check if key exists
     * @param string $key
     * @return bool
     */
    public function exists(int|string $key): bool
    {

        return $this->openswooleTable->exists((string)$key);

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
                        $foreignKey->addToForeignIndex($fromValue, $toKey);
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
     * Add an index
     * @param string[] $fields
     * @return $this
     * @throws IndexException
     */
    public function addIndex(array $fields): self
    {

        if (count($fields) == 0) {
            throw new IndexException('Index must have at least one field');
        }

        foreach ($fields as $field) {

            $ok = false;
            foreach ($this->getColumns() as $column) {

                if ($field == $column->getName()) {
                    $ok = true;
                }

            }

            if (!$ok) {
                throw new IndexException('Field ' . $field . ' not found');
            }

        }

        $this->indexes[implode('|', $fields)] = new Index();

        foreach ($this as $key => $tableFields) {

            $values = [];
            foreach ($fields as $field) {
                $values[] = $tableFields[$field];
            }

            $this->indexes[implode('|', $fields)]
                ->insert(
                    $key ?? throw new \LogicException('Null key error'),
                    count($fields) == 1 ? $values[0] : $values
                )
            ;

        }

        return $this;

    }

    /**
     * Filter table in a result set
     * @param IndexFilter[] $filters
     * @return RecordCollection
     * @throws NotFoundException
     */
    public function filterWithIndex(array $filters): RecordCollection
    {

        $indexes = new Collection();
        foreach ($this->indexes as $fieldsString => $index) {

            $operations = new Collection();
            foreach (array_values($filters) as $filter) {

                $finalFilters = new Collection();
                foreach (explode('|', $fieldsString) as $keyField => $field) {

                    if ($filter->field[$keyField] == $fieldsString[$keyField]) {
                        $finalFilters[$keyField] = $field;
                    }

                }

                if ($finalFilters->count() > 0) {
                    if (!$operations->exists($filter->operator->name)) {
                        $operations[$filter->operator->name] = new Collection();
                    }
                    $operations[$filter->operator->name] = $finalFilters;
                }

            }

            $indexes[$fieldsString] = $operations;

        }

        $indexes->removeEmpty();

        /**
         * @var string $fieldsString
         * @var Collection $operations
         */
        foreach ($indexes as $fieldsString => $operations) {

            /**
             * @var string $operation
             * @var Collection $fields
             */
            foreach ($operations as $operation => $fields) {

                $finalFields = new Collection();
                for ($i = 0; $i < count($operations); $i++) {

                    if (!$fields->valueExists($field = explode('|', $fieldsString)[$i])) {
                        break;
                    }

                    $finalFields[] = $field;

                }

                if ($finalFields->count() > 0) {
                    /** @phpstan-ignore-next-line  */
                    $indexes[$fieldsString][$operation] = $finalFields;
                }

            }

        }

        $resultsKeys = new Collection();
        /**
         * @var string $fieldsString
         * @var Collection $operations
         */
        foreach ($indexes as $fieldsString => $operations) {

            $values = [];
            /**
             * @var string $operation
             * @var string[] $fields
             */
            foreach ($operations as $operation => $fields) {

                foreach ($fields as $field) {
                    /** @var IndexFilter $filter */
                    foreach ($filters as $filter) {

                        if ($filter->operator->name == $operation && $filter->field == $field) {
                            $values[] = $filter->value;
                        }

                    }
                }

                $resultsKeys[] = $this->indexes[$fieldsString]->getKeys(Operator::findByName($operation), $values);

            }

        }

        /** @var Collection $resultKeys */
        foreach ($resultsKeys as $resultKeys) {

            if (!isset($keys)) {
                $keys = new Collection($resultKeys);
            } else {
                $keys->intersect($resultKeys, true);
            }

        }

        if (!isset($keys)) {
            return new RecordCollection();
        }

        $resultset = new RecordCollection();
        /** @var string $key */
        foreach ($keys as $key) {
            $resultset[] = $this->getRecord($key);
        }

        return $resultset;

    }

    /**
     * @param string $foreignKeyName
     * @param Record $from
     * @return Record[]
     */
    public function getJoinedRecords(string $foreignKeyName, Record $from): array
    {

        return $this->foreignKeys[$foreignKeyName]->getForeignRecords($from);

    }

    public function current(): Record
    {

        $data = $this->openswooleTable->current();

        return new Record($this->name, $this->openswooleTable->key() ??
            throw new NotFoundException('No current record'),
            $data ??
            throw new NotFoundException('No current record'),
        );

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

    public function del(int|string $key): bool
    {

        foreach ($this->foreignKeys as $foreignKey) {
            $foreignKey->deleteFromForeignIndex($key);
        }

        return $this->openswooleTable->del((string)$key);

    }

    public function destroy(): bool
    {

        /** @phpstan-ignore-next-line */
        if (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]['class'] != TableRegistry::class) {
            throw new ForbiddenActionException('You must use registry to destroy a table');
        }

        return $this->openswooleTable->destroy();

    }

}