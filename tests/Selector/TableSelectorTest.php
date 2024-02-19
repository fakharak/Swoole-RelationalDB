<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Selector;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\TableSelector;
use function _PHPStan_156ee64ba\React\Promise\Timer\timeout;

class TableSelectorTest extends TestCase
{

    public function testExecuteSingleTable(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelect', 5);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 12.5]);
        $table->set(1, ['name' => 'paul', 'price' => 34.9]);

        $selector = new TableSelector('testSelect');
        $records = $selector->execute();
        self::assertCount(2, $records);
        $this->assertTestSelectResult(0, $records[0]['testSelect']);
        $this->assertTestSelectResult(1, $records[1]['testSelect']);

        $selector = new TableSelector('testSelect');
        $selector->where()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::var, 'price', 'testSelect'),
                ConditionOperator::superior,
                new ConditionElement(ConditionElementType::const, 15)
            ));
        $records = $selector->execute();

        self::assertCount(1, $records);
        $this->assertTestSelectResult(1, $records[0]['testSelect']);

    }

    private function assertTestSelectResult(int $key, Record $result): void
    {

        switch ($key) {
            case 0:
                self::assertEquals($result->getValue('name'), 'john');
                self::assertEquals($result->getValue('price'), 12.5);
                break;
            case 1:
                self::assertEquals($result->getValue('name'), 'paul');
                self::assertEquals($result->getValue('price'), 34.9);
                break;
            default:
                throw new \Exception('Unknown row');
        }

    }

    public function testExecuteJoin(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelectJoin', 5);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 12.5]);
        $table->set(1, ['name' => 'paul', 'price' => 34.9]);

        $table2 = TableRegistry::getInstance()->createTable('testSelectJoinPost', 5);
        $table2->addColumn(new Column('message', ColumnType::string, 255));
        $table2->addColumn(new Column('ownerId', ColumnType::int, 16));
        $table2->create();
        $table2->set(0, ['message' => 'ceci est un test', 'ownerId' => 0]);
        $table2->set(1, ['message' => 'ceci est un autre test', 'ownerId' => 1]);
        $table2->set(2, ['message' => 'ceci est une suite de test', 'ownerId' => 1]);

        $table2->addForeignKey('messageOwner', 'testSelectJoin', 'ownerId');

        $result = (new TableSelector('testSelectJoin'))
            ->join('testSelectJoin', 'messageOwner', 'message')
            ->execute()
        ;
        self::assertCount(3, $result);
        $this->assertTestSelectResultJoin(0, $result[0]['testSelectJoin']);
        $this->assertTestSelectResultJoinPost(0, $result[0]['message']);
        $this->assertTestSelectResultJoin(1, $result[1]['testSelectJoin']);
        $this->assertTestSelectResultJoinPost(1, $result[1]['message']);
        $this->assertTestSelectResultJoin(1, $result[2]['testSelectJoin']);
        $this->assertTestSelectResultJoinPost(2, $result[2]['message']);

    }

    private function assertTestSelectResultJoin(int $key, Record $result): void
    {

        switch ($key) {
            case 0:
                self::assertEquals($result->getValue('name'), 'john');
                self::assertEquals($result->getValue('price'), 12.5);
                break;
            case 1:
                self::assertEquals($result->getValue('name'), 'paul');
                self::assertEquals($result->getValue('price'), 34.9);
                break;
            default:
                throw new \Exception('Unknown row');
        }

    }

    private function assertTestSelectResultJoinPost(int $key, Record $result): void
    {

        switch ($key) {
            case 0:
                self::assertEquals($result->getValue('message'), 'ceci est un test');
                self::assertEquals($result->getValue('ownerId'), 0);
                break;
            case 1:
                self::assertEquals($result->getValue('message'), 'ceci est un autre test');
                self::assertEquals($result->getValue('ownerId'), 1);
                break;
            case 2:
                self::assertEquals($result->getValue('message'), 'ceci est une suite de test');
                self::assertEquals($result->getValue('ownerId'), 1);
                break;
            default:
                throw new \Exception('Unknown row');
        }

    }

    public function testSelectOnIndex()
    {

        $table = TableRegistry::getInstance()->createTable('testTableIndexSelector', 20000);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );
        $table->create();
        $table->addIndex(['name']);
        $table->addIndex(['price']);

        foreach (range(1, 1000) as $value) {
            $table->set($value, ['name' => 'john', 'price' => $value]);
            $table->set($value + 10000, ['name' => 'doe', 'price' => $value]);
        }

        ($query = new TableSelector('testTableIndexSelector'))
            ->where()
            ->firstCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'name', 'testTableIndexSelector'),
                    ConditionOperator::equal,
                    new ConditionElement(ConditionElementType::const, 'doe')
                )
            )->andCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'price', 'testTableIndexSelector'),
                    ConditionOperator::inferiorOrEqual,
                    new ConditionElement(ConditionElementType::const, 100)
                )
            )
        ;
        echo "/";
        $resultset = $query->execute();

        self::assertEquals(100, $resultset->count());

    }

}