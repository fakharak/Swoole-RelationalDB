<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\Contract;

use Small\SwooleDb\Core\Table;

interface PersistenceInterface
{

    /**
     * Load table
     * @param string $name
     * @return Table
     */
    public function load(string $name): Table;

    public function persist(string $name, Table $table);

}