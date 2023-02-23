<?php
/*
 *  This file is a part of small-env
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Core;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Exception\MalformedTable;

class ColumnTest extends TestCase
{

    public function testConstructor()
    {

        $column = new Column('test', ColumnType::string, 36);

        self::assertEquals('test', $column->getName());
        self::assertEquals(ColumnType::string, $column->getType());
        self::assertEquals(36, $column->getSize());

    }

    public function testException()
    {

        try {
            new Column(Column::KEY_COL_NAME, ColumnType::string, 36);
        } catch(\Exception $e) {}

        self::assertInstanceOf(MalformedTable::class, $e);

    }

}