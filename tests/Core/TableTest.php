<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Core;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Table;

class TableTest extends TestCase
{

    public function testGetRecord()
    {

        $table = new Table('testTable', 10);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 10.2]);
        $record = $table->getRecord(0);

        self::assertEquals('john', $record->getValue('name'));
        self::assertEquals(10.2, $record->getValue('price'));

        $table->setName('changed');
        self::assertEquals('changed', $table->getName());

    }

}