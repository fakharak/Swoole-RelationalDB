<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Registry;

use Small\SwooleDb\Exception\TableAlreadyExists;
use Small\SwooleDb\Exception\TableNotExists;
use Small\SwooleDb\Registry\Trait\RegistryTrait;
use Small\SwooleDb\Core\Table;

/**
 *
 */
final class TableRegistry
{

    use RegistryTrait;

    /** @var Table[] */
    private array $tables = [];

    /**
     * Load table from channel
     * @param string $tableName
     * @param string $channel
     * @return Table
     * @throws \Small\SwooleDb\Exception\SmallSwooleDbException
     */
    public function load(string $tableName, string $channel = PersistenceRegistry::DEFAULT): Table
    {

        $this->tables[$tableName] = PersistenceRegistry::getInstance()->getChannel($channel)->load($tableName);

        return $this->tables[$tableName];

    }

    /**
     * Persist a table
     * @param string $tableName
     * @param string $channel
     * @return $this
     * @throws TableNotExists
     * @throws \Small\SwooleDb\Exception\SmallSwooleDbException
     */
    public function persist(string $tableName, string $channel = PersistenceRegistry::DEFAULT): self
    {

        if (!array_key_exists($tableName, $this->tables)) {
            throw new TableNotExists('The table ' . $tableName . ' does\'nt exists');
        }

        PersistenceRegistry::getInstance()->getChannel($channel)->persist($tableName, $this->tables[$tableName]);

        return $this;
    }

    /**
     * Create a table
     * @param string $tableName
     * @param int $rowSize
     * @return Table
     * @throws TableAlreadyExists
     */
    public function createTable(string $tableName, int $rowSize): Table
    {

        if (array_key_exists($tableName, $this->tables)) {
            throw new TableAlreadyExists('The table ' . $tableName . ' already exists');
        }

        return $this->tables[$tableName] = new Table($tableName, $rowSize);

    }

    /**
     * Get a table by name
     * @param string $tableName
     * @return Table
     * @throws TableNotExists
     */
    public function getTable(string $tableName): Table
    {

        if (!array_key_exists($tableName, $this->tables)) {
            throw new TableNotExists('The table ' . $tableName . ' does\'nt exists');
        }

        return $this->tables[$tableName];

    }

    /**
     * Destroy a table
     * @param string $tableName
     * @return $this
     * @throws TableNotExists
     */
    public function destroy(string $tableName): self
    {
        if (!array_key_exists($tableName, $this->tables)) {
            throw new TableNotExists('The table ' . $tableName . ' does\'nt exists');
        }

        $this->tables[$tableName]->destroy();
        unset($this->tables[$tableName]);

        return $this;
    }

}