<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Selector;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\TableSelector;

class TableSelectorTest extends TestCase
{

    public function testExecute(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelect', 5);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 12.5]);
        $table->set(1, ['name' => 'paul', 'price' => 34.9]);

        $selector = new TableSelector('testSelect');
        $selector->where()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::var, 'name', 'testSelect'),
                ConditionOperator::superior,
                new ConditionElement(ConditionElementType::const, 15)
            ));
        $records = $selector->execute();

        self::assertCount(1, $records);
    }

}