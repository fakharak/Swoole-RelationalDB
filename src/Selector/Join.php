<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector;

use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Core\Resultset;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Registry\TableRegistry;

class Join
{

    protected Table $from;

    public function __construct(
        readonly protected string $fromTableName,
        readonly protected string $foreignKeyName,
        readonly protected string $alias,
    ) {

        $this->from = TableRegistry::getInstance()->getTable($this->fromTableName);

    }

    public function getToTableName(): string
    {

        return $this->from->getForeignTable($this->foreignKeyName);

    }

    /**
     * Get foreign records for a record of from table
     * @param RecordCollection $fromRecord
     * @return Resultset
     */
    public function get(RecordCollection $fromRecord): Resultset
    {

        return $this->from->getJoinedRecords($this->foreignKeyName, $fromRecord, $this->foreignKeyName);

    }

    /**
     * @return string
     */
    public function getFromTableName(): string
    {

        return $this->fromTableName;

    }

    /**
     * @return string
     */
    public function getForeignKeyName(): string
    {

        return $this->foreignKeyName;

    }

    /**
     * @return string
     */
    public function getAlias(): string
    {

        return $this->alias;

    }

}