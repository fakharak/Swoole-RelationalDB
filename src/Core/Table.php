<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

if (!\class_exists('\OpenSwoole\Table')) {
    \class_alias('\Swoole\Table', '\OpenSwoole\Table');
}

use Small\Collection\Collection\Collection;
use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\Contract\IdGeneratorInterface;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Exception\MalformedTable;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Core\Index\ForeignKey;
use Small\SwooleDb\Core\Index\Index;
use Small\SwooleDb\Exception\FieldValueIsNull;
use Small\SwooleDb\Exception\ForbiddenActionException;
use Small\SwooleDb\Exception\IndexException;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Exception\UnknownForeignKeyException;
use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;
use Small\SwooleDb\Selector\TableSelector;

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

    protected bool $created = false;

    protected IdGeneratorInterface|null $idGenerator = null;

    public function __construct(
        protected string $name,
        private int $maxSize,
        float $conflict_proportion = 0.2
    ) {

        $this->openswooleTable = new \OpenSwoole\Table($this->maxSize, $conflict_proportion);

    }

    public function uniqueId(IdGeneratorInterface $idGenerator): self
    {

        $this->idGenerator = $idGenerator;

        return $this;

    }

    public function hasIndex(string $field): bool
    {

        return array_key_exists($field, $this->indexes);

    }

    public function create(): self
    {

        $this->openswooleTable->create();

        $this->created = true;

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

    public function hasColumn(string $column): bool
    {

        return key_exists($column, $this->columns);

    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {

        return $this->columns;

    }

    protected function formatValue(int|string $key, string $column, string|int|float|null $value): string|int|float|null
    {

        $isNull = $this->openswooleTable->get((string)$key, $column . '::null') == 1;
        if ($isNull) {
            throw new FieldValueIsNull('Value for column ' . $column . ' is null');
        }

        if (in_array($this->getColumns()[$column]->getType(), [ColumnType::int, ColumnType::float])) {

            if ($this->openswooleTable->get($column . '::sign') == 1) {

                if (!is_float($value) && !is_int($value)) {
                    throw new \LogicException('Can\'t sign value');
                }

                $value = -$value;
            }
        }

        return $value;

    }

    /**
     * @param (int|float|string|null)[] $rawRecord
     * @param bool $abs
     * @return (int|float|string|null)[]
     */
    protected function setMetasValues(array $rawRecord, bool $abs): array
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
                    throw new \LogicException('Impossible value for type (' . gettype($value) . ')');
                }

                $array[$column . '::sign'] = $value < 0 ? 1 : 0;
                if($abs) {
                    $value = abs($value);
                }
            }

            switch($this->getColumns()[$column]->getType()) {
                case ColumnType::int:
                    if (!is_int($value)) {
                        throw new \LogicException('Impossible value for type (' . gettype($value) . ')');
                    }
                    $array[$column] = (int)$value;
                    break;
                case ColumnType::float:
                    if (!is_int($value) && !is_float($value)) {
                        throw new \LogicException('Impossible value for type (' . gettype($value) . ')');
                    }
                    $array[$column] = (float)$value;
                    break;
                case ColumnType::string:
                    if (!is_scalar($value)) {
                        throw new \LogicException('Impossible value for type (' . gettype($value) . ')');
                    }
                    $array[$column] = (string)$value;
                    break;
            }
        }

        return $array;

    }

    /**
     * @param int|string $key
     * @param string $column
     * @return string|int|float|Collection|null
     * @throws FieldValueIsNull
     */
    public function get(int|string $key, string $column = ''): string|int|float|Collection|null
    {

        /** @var (int|float|string)[]|null $rawResult */
        $rawResult = $this->openswooleTable->get((string)$key);

        if ($column !== '') {

            if (!is_array($rawResult)) {
                throw new \LogicException('Wrong type');
            }

            return $this->formatValue($key, $column, $rawResult[$column]);

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

        return new Collection($result);

    }

    /**
     * @param string|null $key
     * @param (string|int|float|null)[] $setValues
     * @param bool $abs
     * @return string|null
     * @throws FieldValueIsNull
     * @throws NotFoundException
     * @throws SyntaxErrorException
     * @throws \Small\SwooleDb\Exception\TableNotExists
     */
    public function set(string|null $key, array $setValues, bool $abs): string|null
    {

        if ($key === null) {

            if ($this->idGenerator === null) {
                throw new SyntaxErrorException('key at null without a generator');
            }

            do {
                $key = $this->idGenerator->generate();
            } while ($this->exists($key));

        }

        $result = [];
        foreach ($setValues as $field => $item) {
            if (array_key_exists($field, $columns = $this->getColumns()) && $columns[$field] !== null) {
                $result[$field] = $item === null ? $columns[$field]->getNullValue() : $item;
            }
        }

        foreach ($this->indexes as $fieldsString => $index) {

            $indexValues = [];
            foreach (explode('|', $fieldsString) as $field) {
                $indexValues[] = $setValues[$field];
            }

            $index->insert($key, $indexValues);

        }

        $this->addToForeignKeys($key, $setValues);

        if ($this->openswooleTable->set($key, $this->setMetasValues($result, $abs))) {
            return $key;
        } else {
            return null;
        }

    }

    /**
     * @param (int|float|string|null)[] $values
     * @return $this
     * @throws FieldValueIsNull
     * @throws NotFoundException
     * @throws \Small\SwooleDb\Exception\TableNotExists
     */
    public function addToForeignKeys(string $key, array $values): self
    {

        foreach ($this->foreignKeys as $name => $foreignKey) {

            if ($foreignKey->getFromField() != Column::KEY_COL_NAME && $foreignKey->getToField() == Column::KEY_COL_NAME) {

                $foreignKey->addToForeignIndex(
                    $key,
                    $values[$foreignKey->getFromField()],
                );

                $foreignKey->getReflected()
                    ->addToForeignIndex(
                        $values[$foreignKey->getFromField()],
                        $key,
                    );

            } else if ($foreignKey->getFromField() == Column::KEY_COL_NAME && $foreignKey->getToField() == Column::KEY_COL_NAME) {

                $foreignKey->addToForeignIndex($key, $key);

                $foreignKey->getReflected()
                    ->addToForeignIndex($key, $key)
                ;

            }

        }

        return $this;

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

        $collection = $this->get($key);
        if (!$collection instanceof Collection) {
            throw new \LogicException('Wrong type');
        }

        return new Record($this->getName(), $key, $collection);

    }

    /**
     * Check if key exists
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
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
    public function addForeignKey(string $name, string $toTableName, string $fromField, string $toField = Column::KEY_COL_NAME): self
    {

        if ($toField != Column::KEY_COL_NAME) {
            $this->addIndex([$toField], ForeignKey::INDEX_MAX_SIZE, Index::KEY_MAX_SIZE);
        }

        if (!isset($this->getColumns()[$fromField]) && $fromField != Column::KEY_COL_NAME) {
            throw new NotFoundException('Field \'' . $fromField . '\' not exists in table \'' . $this->getName() . '\' on foreign key creation');
        }

        $toTable = TableRegistry::getInstance()->getTable($toTableName);

        if (!isset($toTable->getColumns()[$toField]) && $toField != Column::KEY_COL_NAME) {
            throw new NotFoundException('Field \'' . $toField . '\' not exists in table \'' . $toTable->getName() . '\' on foreign key creation');
        }

        $foreignKey = new ForeignKey($name, $this->name, $fromField, $toTableName, $toField, ForeignKeyType::from);

        $foreignKeyReflection = new ForeignKey($name, $toTableName, $toField, $this->name, $fromField, ForeignKeyType::to);

        $foreignKey->setReflected($foreignKeyReflection);
        $foreignKeyReflection->setReflected($foreignKey);

        $this->foreignKeys[$name] = $foreignKey;
        $toTable->foreignKeys[$toField == Column::KEY_COL_NAME ? $this->getName() . 's' : $toField] = $foreignKeyReflection;

        return $this;

    }

    public function getForeignTable(string $foreignKeyName): string
    {

        if (!array_key_exists($foreignKeyName, $this->foreignKeys)) {
            throw new UnknownForeignKeyException('Foreign key ' . $foreignKeyName . ' not found');
        }

        return $this->foreignKeys[$foreignKeyName]->getToTableName();

    }

    public function count(): int
    {

        return $this->openswooleTable->count();

    }

    /**
     * Add an index
     * @param string[] $fields
     * @return $this
     * @throws IndexException
     */
    public function addIndex(array $fields, int $indexMaxSize, int $indexDataMaxSize): self
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

        $index = $this->indexes[$name = implode('|', $fields)] = new Index(
            $this->name . '_index_' . $name,
            $indexMaxSize,
            $indexDataMaxSize
        );

        if ($this->created) {
            foreach ($this as $key => $record) {

                $values = [];
                foreach ($fields as $field) {
                    $values[] = $record[$field];
                }

                $index->insert($key ?? '', $values);

            }
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
     * @param RecordCollection $from
     * @return Resultset
     */
    public function getJoinedRecords(string $foreignKeyName, RecordCollection $from, string $alias): Resultset
    {

        if (!array_key_exists($alias, $this->foreignKeys)) {
            throw new UnknownForeignKeyException('Foreign key ' . $alias . ' does not exist');
        }

        return $this->foreignKeys[$alias]->getForeignRecords($from[$this->name], $alias);

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

    public function del(string $key): bool
    {

        foreach ($this->foreignKeys as $foreignKey) {
            $foreignKey->deleteFromForeignIndex($key);
        }

        foreach ($this->indexes as $indexKey => $index) {

            $values = [];
            foreach (explode('|', $indexKey) as $field) {

                $value = $this->get($key, $field);

                if ($value instanceof Collection) {
                    throw new \LogicException('Wrong type');
                }

                $values[] = $value;

            }

            $index->remove($key, $values);

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
    
    /**
     * This function returns the original Swoole Table instead of Wrapper Table
     *
     * @return mixed
     */
    public function getSwooleTable(): mixed {
        return $this->openswooleTable;
    }
}
