<?php

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Exception\DeleteFailException;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\TableRegistry;

class Record
{

    public function __construct(
        protected string $tableName,
        protected mixed $key,
        protected array $data,
    ) {}

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
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * Get value of a field
     * @param string $fieldName
     * @return mixed
     * @throws NotFoundException
     */
    public function getValue(string $fieldName): mixed
    {

        if (!array_key_exists($fieldName, $this->data)) {
            throw new NotFoundException('Field ' . $fieldName . ' not exists in record data');
        }

        return $this->data[$fieldName];

    }

    /**
     * Set value of a field
     * @param string $fieldName
     * @param mixed $value
     * @return $this
     * @throws NotFoundException
     */
    public function setValue(string $fieldName, mixed $value): self
    {

        if (!array_key_exists($fieldName, $this->data)) {
            throw new NotFoundException('Field ' . $fieldName . ' not exists in record data');
        }

        $this->data[$fieldName] = $value;

        return $this;

    }

    /**
     * Get fields as array
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data to array
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Duplicate record with new key
     * @param mixed $newKey
     * @return Record
     */
    public function duplicate(mixed $newKey): Record
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
        $this->getTable()->set($this->key, $this->data);

        return $this;
    }

    /**
     * Remove record from table
     * @return $this
     * @throws DeleteFailException
     */
    public function delete(): self
    {
        if (!$this->getTable()->del($this->key)) {
            throw new DeleteFailException('Fail to delete record #' . $this->key);
        }

        return $this;
    }

}