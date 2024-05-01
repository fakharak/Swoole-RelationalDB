<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\Collection\Collection\Collection;
use Small\SwooleDb\Exception\DeleteFailException;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Exception\WrongFormatException;
use Small\SwooleDb\Registry\TableRegistry;

class Record implements \ArrayAccess
{

    protected Collection $data;

    /**
     * @param string $tableName
     * @param string $key
     * @param mixed[]|Collection $data
     * @phpstan-ignore-next-line
     */
    public function __construct(
        protected string $tableName,
        protected string|null $key,
        array|Collection $data,
    ) {

        if (is_array($data)) {
            $this->data = new Collection($data);
        } else {
            $this->data = $data;
        }

    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return $this
     */
    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getTable(): Table
    {
        return TableRegistry::getInstance()->getTable($this->tableName);
    }

    /**
     * @return string
     */
    public function getKey(): string|null
    {

        return $this->key;

    }

    /**
     * Get value of a field
     * @param string $fieldName
     * @return float|int|string|null
     * @throws NotFoundException
     */
    public function getValue(string $fieldName): float|int|string|null
    {

        if (!$this->data->offsetExists($fieldName)) {
            throw new NotFoundException('Field ' . $fieldName . ' not exists in record data');
        }

        if (
            !is_int($this->data[$fieldName]) &&
            !is_float($this->data[$fieldName]) &&
            !is_string($this->data[$fieldName]) &&
            !is_null($this->data[$fieldName])
        ) {
            throw new WrongFormatException('Field ' . $fieldName . ' has wrong type');
        }

        return $this->data[$fieldName];

    }

    /**
     * Set value of a field
     * @param string $fieldName
     * @param string|int|float|null $value
     * @return $this
     * @throws NotFoundException
     */
    public function setValue(string $fieldName, string|int|float|null $value): self
    {

        if (!$this->data->offsetExists($fieldName)) {
            throw new NotFoundException('Field ' . $fieldName . ' not exists in record data');
        }

        $this->data[$fieldName] = $value;

        return $this;

    }

    /**
     * Get fields as array
     * @return Collection
     */
    public function getData(): Collection
    {
        return $this->data;
    }

    /**
     * Set data to array
     * @param string|int|float|null[]|Collection $data
     * @return $this
     * @phpstan-ignore-next-line
     */
    public function setData(array|Collection $data): self
    {

        if (is_array($data)) {
            $data = new Collection($data);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Duplicate record with new key
     * @param string $newKey
     * @return Record
     */
    public function duplicate(string $newKey): Record
    {

        $newRecord = clone $this;
        $newRecord->key = $newKey;

        return $newRecord;

    }

    /**
     * Persist record
     * @return $this
     */
    public function persist(): self
    {
        $this->key = $this->getTable()->set($this->key, $this->data->toArray());

        return $this;
    }

    /**
     * Remove record from table
     * @return $this
     * @throws DeleteFailException
     */
    public function delete(): self
    {

        if ($this->key === null) {
            throw new DeleteFailException('Key not set');
        }

        if (!$this->getTable()->del($this->key)) {
            throw new DeleteFailException('Fail to delete record #' . $this->key);
        }

        return $this;
    }

    /**
     * @param string $offset
     * @return bool
     */
    #[\Override] public function offsetExists(mixed $offset): bool
    {
        return $this->data->offsetExists($offset);
    }

    /**
     * @param string $offset
     * @return string|int|float|null
     */
    #[\Override] public function offsetGet(mixed $offset): mixed
    {

        /** @var string|int|float|null $value */
        $value = $this->data->offsetGet($offset);

        return $value;

    }

    /**
     * @param string $offset
     * @param string|int|float|null $value
     * @return void
     */
    #[\Override] public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data->offsetSet($offset, $value);
    }

    /**
     * @param string $offset
     * @return void
     */
    #[\Override] public function offsetUnset(mixed $offset): void
    {
        $this->data->offsetUnset($offset);
    }


}