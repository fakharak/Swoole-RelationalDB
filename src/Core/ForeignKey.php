<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\ForeignKeyType;
use Small\SwooleDb\Exception\FileNotFoundException;
use Small\SwooleDb\Exception\TableNotExists;
use Small\SwooleDb\Registry\TableRegistry;

class ForeignKey
{

    const FOREIGN_KEY_TABLE_PREFIX = '_FOREIGN_KEY_';
    const FROM_INDEX_TABLE_PREFIX = self::FOREIGN_KEY_TABLE_PREFIX . 'FROM_';
    const TO_INDEX_TABLE_PREFIX = self::FOREIGN_KEY_TABLE_PREFIX . 'TO_';

    const INDEX_MAX_SIZE = 1048576;

    protected Table $index;

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
            $this->index = TableRegistry::getInstance()->getTable($prefix . $this->keyName);
        } catch (TableNotExists) {
            $this->index = TableRegistry::getInstance()->createTable($prefix . $this->keyName, self::INDEX_MAX_SIZE);
            self::createIndexTable(TableRegistry::getInstance()->getTable($this->toTable), $this->toField);
        }
    }

    /**
     * Create an index table
     * @param Table $table
     * @param string $indexField
     * @return void
     * @throws FileNotFoundException
     * @throws TableNotExists
     * @throws \Small\SwooleDb\Exception\MalformedTable
     */
    private function createIndexTable(Table $table, string $indexField): void
    {

        $type = ColumnType::string;
        $size = 256;

        if (!isset($type)) {
            throw new FileNotFoundException('The field \'' . $indexField . '\' does\'nt exists at foreign key creation');
        }

        $this->index->addColumn(new Column('foreignKey', $type, $size));
        $this->index->addColumn(new Column('valid', ColumnType::int, 1));

        $this->index->create();

    }

    /**
     * Add to "from" index
     * @param mixed $value
     * @param mixed $foreignKey
     * @return self
     */
    public function addToIndex(mixed $value, mixed $foreignKey): self
    {

        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            if (!$this->index->exists($value . '_' . $i)) {
                break;
            }
            if ($this->index->get($value . '_' . $i)['foreignKey'] == $foreignKey) {
                return $this;
            }
        }

        $this->index->set($value . '_' . $i, ['foreignKey' => $foreignKey, 'valid' => 1]);

        return $this;

    }

    /**
     * Get foreign record
     * @param $value
     * @return Record[]
     */
    public function getForeignRecords(Record $record): array
    {

        $value = $this->fromField == '_key' ? $record->getKey() : $record->getValue($this->fromField);
        $resultset = [];
        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            if ($this->index->exists($value . '_' . $i)) {
                $foreignKey = $this->index->get($value . '_' . $i);
                if ($foreignKey['valid'] == 1) {
                    $resultset[] = TableRegistry::getInstance()->getTable($this->toTable)->getRecord($foreignKey['foreignKey']);
                }
            } else {
                break;
            }
        }

        return $resultset;

    }

    /**
     * Delete key from index
     * @param $value
     * @return void
     */
    public function deleteFromIndex($value): void
    {

        for ($i = 0; $i < self::INDEX_MAX_SIZE; $i++) {
            if ($record = $this->index->get($value . '_' . $i)) {
                $record['valid'] = 0;
                $this->index->set($value . '_' . $i, $record);
            }
        }

    }

}