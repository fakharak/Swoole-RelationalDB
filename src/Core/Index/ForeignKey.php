<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\Index;

use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Core\Resultset;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Exception\TableNotExists;
use Small\SwooleDb\Registry\TableRegistry;

class ForeignKey
{

    const FOREIGN_KEY_TABLE_PREFIX = '_FOREIGN_KEY_';
    const FROM_INDEX_TABLE_PREFIX = self::FOREIGN_KEY_TABLE_PREFIX . 'FROM_';
    const TO_INDEX_TABLE_PREFIX = self::FOREIGN_KEY_TABLE_PREFIX . 'TO_';

    const INDEX_MAX_SIZE = 1048576;

    protected Table $foreignIndex;

    public function __construct(
        protected string $keyName,
        protected string $fromTable,
        protected string $fromField,
        protected string $toTable,
        protected string $toField,
        protected ForeignKeyType $type,
    ) {

        switch ($this->type) {
            case ForeignKeyType::from:
                $prefix = static::FROM_INDEX_TABLE_PREFIX;
                break;
            case ForeignKeyType::to:
                $prefix = static::TO_INDEX_TABLE_PREFIX;
                break;
        }

        try {
            $this->foreignIndex = TableRegistry::getInstance()->getTable($prefix . $this->keyName);
        } catch (TableNotExists) {
            $this->foreignIndex = TableRegistry::getInstance()->createTable($prefix . $this->keyName, self::INDEX_MAX_SIZE);
            $this->createForeignIndexTable();
        }
    }

    public function getToTableName(): string
    {

        return $this->toTable;

    }

    /**
     * Create an index table
     * @return self
     * @throws \Small\SwooleDb\Exception\MalformedTable
     */
    private function createForeignIndexTable(): self
    {

        $type = ColumnType::string;
        $size = 256;

        $this->foreignIndex->addColumn(new Column('foreignKey', $type, $size));
        $this->foreignIndex->addColumn(new Column('valid', ColumnType::int, 1));

        $this->foreignIndex->create();

        return $this;

    }

    /**
     * Add to "from" index
     * @param mixed $value
     * @param mixed $foreignKey
     * @return $this
     * @throws \Small\SwooleDb\Exception\FieldValueIsNull
     */
    public function addToForeignIndex(mixed $value, mixed $foreignKey): self
    {

        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            if (!$this->foreignIndex->exists($value . '_' . $i)) {
                break;
            }
            if ($this->foreignIndex->get($value . '_' . $i, 'foreignKey') == $foreignKey) {
                return $this;
            }
        }

        $this->foreignIndex->set($value . '_' . $i, ['foreignKey' => $foreignKey, 'valid' => 1]);

        return $this;

    }

    /**
     * Get foreign record
     * @param Record $record
     * @param string|null $alias
     * @return Resultset
     * @throws NotFoundException
     * @throws TableNotExists
     */
    public function getForeignRecords(Record $record, string $alias = null): Resultset
    {

        $value = $this->fromField == '_key' ? $record->getKey() : $record->getValue($this->fromField);
        $resultset = new Resultset();
        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            if ($this->foreignIndex->exists($value . '_' . $i)) {
                $foreignKey = $this->foreignIndex->getRecord($value . '_' . $i);
                if ($foreignKey->getValue('valid') == 1) {
                    $resultset[] = new RecordCollection([
                        $alias ?? $this->toTable =>
                        TableRegistry::getInstance()
                        ->getTable($this->toTable)
                        ->getRecord(
                            is_string($foreignKey->getValue('foreignKey'))
                            ? $foreignKey->getValue('foreignKey')
                            : throw new \LogicException('Foreign key must be string')
                        )
                    ]);
                }
            } else {
                break;
            }
        }

        return $resultset;

    }

    /**
     * Delete key from index
     * @param string|int $value
     * @return $this
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function deleteFromForeignIndex(string|int $value): self
    {

        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            try {
                $record = $this->foreignIndex->getRecord($value . '_' . $i);
                $record->setValue('valid', 0);
                $record->persist();
            } catch(NotFoundException) {}
        }

        return $this;

    }

}