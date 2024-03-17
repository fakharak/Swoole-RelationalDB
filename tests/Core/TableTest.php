<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Core;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Enum\Operator;
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

    public function testFilterByIndex()
    {

        $table = new Table('testTableIndex', 100000);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );

        $table->addIndex(['name'], 10000, 256);
        $table->addIndex(['price'], 10000, 256);

        $table->create();

        $name = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        foreach (range(1, 10000) as $value) {
            $table->set($value, ['name' => $name[rand(1, strlen($name)) - 1], 'price' => rand(1, 1000000) / 100]);
        }

        $total = 0;
        for ($i = 0; $i < strlen($name); $i++) {

            $total += $table->filterWithIndex([
                    new IndexFilter(Operator::equal, 'name', $name[$i])
                ])->count();

        }

        self::assertEquals(10000, $total);

    }

}