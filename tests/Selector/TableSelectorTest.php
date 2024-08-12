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
use Small\SwooleDb\Core\IdGenerator\ULID;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Bean\OrderByField;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Enum\OrderBySens;
use Small\SwooleDb\Selector\TableSelector;
use function _PHPStan_156ee64ba\React\Promise\Timer\timeout;
use function _PHPStan_cc8d35ffb\Symfony\Component\String\b;

class TableSelectorTest extends TestCase
{

    public function testExecuteSingleTable(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelect', 5);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $key = 0; $table->set($key, ['name' => 'john', 'price' => 12.5]);
        $key = 1; $table->set($key, ['name' => 'paul', 'price' => 34.9]);

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

    public function testExecuteSingleTablePaginated(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelectPaginated', 200);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $key = 0; $table->set($key, ['name' => 'john', 'price' => 12.5]);
        for ($i = 0; $i < 101; $i++) {
            $key = $i + 1; $table->set($key, ['name' => 'paul' . $i, 'price' => 34.9 + $i]);
        }

        $selector = new TableSelector('testSelectPaginated');
        $selector->where()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::var, 'price', 'testSelectPaginated'),
                ConditionOperator::superior,
                new ConditionElement(ConditionElementType::const, 15)
            ));

        $selector->addOrderBy(
            new OrderByField(
                'testSelectPaginated',
                Column::KEY_COL_NAME,
            )
        );

        $page = 1;
        $i = 0;
        while(($records = $selector->paginate($page, $pageSize = 10)->execute())->count() > 0) {

            if ($page <= 10) {
                self::assertCount($pageSize, $records);
            } else {
                self::assertCount(1, $records);
            }

            foreach ($records as $record) {
                self::assertEquals(34.9 + $i, $record['testSelectPaginated']->getValue('price'));
                $i++;
            }

            $page++;

        }

    }

    public function testExecuteSingleTableLimit(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelectLimit', 102);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 12.5]);
        for ($i = 0; $i < 101; $i++) {
            $key = $i + 1; $table->set($key, ['name' => 'paul' . $i, 'price' => 34.9 + $i]);
        }

        $selector = new TableSelector('testSelectLimit');
        $selector->where()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::var, 'price', 'testSelectLimit'),
                ConditionOperator::superior,
                new ConditionElement(ConditionElementType::const, 15)
            ));

        $selector->addOrderBy(
            new OrderByField(
                'testSelectLimit',
                Column::KEY_COL_NAME,
            )
        );

        $page = 1;
        $i = 0;
        $records = $selector
            ->limit(10, $pageSize = 10)
            ->execute();

        self::assertCount($pageSize, $records);

        $i = 10;
        foreach ($records as $record) {
            self::assertEquals(34.9 + $i, $record['testSelectLimit']->getValue('price'));
            $i++;
        }

    }

    public function testExecuteOrderByTwoTables(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelectOrderBy1', 3);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::int, 8));
        $table->uniqueId(new ULID());
        $table->create();

        $table2 = TableRegistry::getInstance()->createTable('testSelectOrderBy2', 4);
        $table2->addColumn(new Column('name', ColumnType::string, 255));
        $table2->addColumn(new Column('price', ColumnType::int, 8));
        $table2->addColumn(new Column('managerKey', ColumnType::string, 25));
        $table2->uniqueId(new ULID());
        $table2->addForeignKey('manager', 'testSelectOrderBy1', 'managerKey');
        $table2->create();

        $key1 = $table->set(null, ['name' => 'john', 'price' => 12]);
        $key2 = $table->set(null, ['name' => 'john2', 'price' => 11]);
        $key3 = $table->set(null, ['name' => 'john3', 'price' => 10]);

        $table2->set(null, ['name' => 'doe', 'price' => 13, 'managerKey' => $key2]);
        $table2->set(null, ['name' => 'doe2', 'price' => 12, 'managerKey' => $key2]);
        $table2->set(null, ['name' => 'doe3',  'price' => 10, 'managerKey' => $key1]);
        $table2->set(null, ['name' => 'doe4', 'price' => 10, 'managerKey' => $key3]);

        $records = (new TableSelector('testSelectOrderBy2'))
            ->join('testSelectOrderBy2', 'manager')
            ->addOrderBy(
                new OrderByField(
                    'testSelectOrderBy2',
                    'price',
                    OrderBySens::descending,
                )
            )->addOrderBy(
                new OrderByField(
                    'manager',
                    'price',
                )
            )->execute()
        ;

        self::assertCount(4, $records);
        self::assertEquals('john2', $records[0]['manager']->getValue('name'));
        self::assertEquals('doe', $records[0]['testSelectOrderBy2']->getValue('name'));
        self::assertEquals('john2', $records[1]['manager']->getValue('name'));
        self::assertEquals('doe2', $records[1]['testSelectOrderBy2']->getValue('name'));
        self::assertEquals('john3', $records[2]['manager']->getValue('name'));
        self::assertEquals('doe4', $records[2]['testSelectOrderBy2']->getValue('name'));
        self::assertEquals('john', $records[3]['manager']->getValue('name'));
        self::assertEquals('doe3', $records[3]['testSelectOrderBy2']->getValue('name'));

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
        $table2->addColumn(new Column('ownerId', ColumnType::string, 16));
        $table2->addForeignKey('messageOwner', 'testSelectJoin', 'ownerId');
        $table2->create();


        $table2->set(0, ['message' => 'ceci est un test', 'ownerId' => '0']);
        $table2->set(1, ['message' => 'ceci est un autre test', 'ownerId' => '1']);
        $table2->set(2, ['message' => 'ceci est une suite de test', 'ownerId' => '1']);

        $result = (new TableSelector('testSelectJoin', 'user'))
            ->join('user', 'testSelectJoinPosts', 'message')
            ->execute()
        ;
        self::assertCount(3, $result);
        $this->assertTestSelectResultJoin(0, $result[0]['user']);
        $this->assertTestSelectResultJoinPost(0, $result[0]['message']);
        $this->assertTestSelectResultJoin(1, $result[1]['user']);
        $this->assertTestSelectResultJoinPost(1, $result[1]['message']);
        $this->assertTestSelectResultJoin(1, $result[2]['user']);
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

        $table = TableRegistry::getInstance()->createTable('testTableIndexSelector', 101);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );

        $table->addIndex(['name'], 101, 256);
        $table->addIndex(['price'], 101, 64);

        $table->create();
        foreach (range(1, 100) as $value) {
            $table->set($value, ['name' => 'john', 'price' => $value]);
            $table->set($value + 100, ['name' => 'doe', 'price' => $value]);
        }

        ($query = new TableSelector('testTableIndexSelector', 'worker'))
            ->where()
            ->firstCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'name', 'worker'),
                    ConditionOperator::equal,
                    new ConditionElement(ConditionElementType::const, 'john')
                )
            )->andCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'price', 'worker'),
                    ConditionOperator::inferiorOrEqual,
                    new ConditionElement(ConditionElementType::const, 10)
                )
            )
        ;

        $resultset = $query->execute();

        self::assertEquals(10, $resultset->count());

    }

    public function testExecuteJoinOnIndex(): void
    {

        $table = TableRegistry::getInstance()->createTable('testSelectJoinIndex', 100);
        $table->addColumn(new Column('name', ColumnType::string, 255));
        $table->addColumn(new Column('price', ColumnType::float));

        $table->addIndex(['name'], 10, 255);

        $table->create();

        $table2 = TableRegistry::getInstance()->createTable('testSelectJoinIterationsIndex', 1000);
        $table2->addColumn(new Column('iterator', ColumnType::int, 32));
        $table2->addColumn(new Column('ownerId', ColumnType::string, 16));
        $table2->addIndex(['iterator'], 1000, 32);
        $table2->addForeignKey('owner', 'testSelectJoinIndex', 'ownerId');
        $table2->create();

        $table->set('0', ['name' => 'john', 'price' => 5.25]);
        $table->set('1', ['name' => 'paul', 'price' => 12.75]);
        foreach (range(1, 100) as $value) {
            $table2->set((string)$value, ['iterator' => $value, 'ownerId' => '0']);
            $table2->set((string)$value + 100, ['iterator' => $value, 'ownerId' => '1']);
        }

        ($query = new TableSelector('testSelectJoinIndex'))
            ->join('testSelectJoinIndex', 'testSelectJoinIterationsIndexs', 'iterations')
            ->where()
            ->firstCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'name', 'testSelectJoinIndex'),
                    ConditionOperator::equal,
                    new ConditionElement(ConditionElementType::const, 'john')
                )
            )->andCondition(
                new Condition(
                    new ConditionElement(ConditionElementType::var, 'iterator', 'iterations'),
                    ConditionOperator::inferiorOrEqual,
                    new ConditionElement(ConditionElementType::const, 10)
                )
            )
        ;
        $result = $query->execute();

        self::assertCount(10, $result);

    }

}