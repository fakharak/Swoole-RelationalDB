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
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Exception\DeleteFailException;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\TableRegistry;

class RecordTest extends TestCase
{

    public function testRecord()
    {

        $table = TableRegistry::getInstance()->createTable('testTable', 10);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );
        $table->create();
        $table->set(0, ['name' => 'john', 'price' => 10.2]);
        $record = $table->getRecord(0);

        $table = TableRegistry::getInstance()->createTable('changed', 1);
        $table->addColumn(
            new Column('name', ColumnType::string, 256)
        );
        $table->addColumn(
            new Column('price', ColumnType::float)
        );
        $table->create();

        self::assertEquals('testTable', $record->getTableName());

        $record->setTableName('changed');

        self::assertEquals('changed', $record->getTableName());

        self::assertEquals(0, $record->getKey());

        self::assertEquals('john', $record->getValue('name'));
        self::assertEquals(10.2, $record->getValue('price'));

        $record->setValue('price', 5.4);

        self::assertEquals(5.4, $record->getValue('price'));

        $array = $record->getData();
        self::assertCount(2, $array);
        self::assertEquals('john', $array['name']);
        self::assertEquals(5.4, $array['price']);

        $copy = $record->duplicate(10);
        self::assertEquals(10, $copy->getKey());
        self::assertEquals('john', $copy->getValue('name'));
        self::assertEquals(5.4, $copy->getValue('price'));

        $copy->persist();

        $table = TableRegistry::getInstance()->getTable('testTable');
        $record = $table->getRecord(0);
        $record->delete();

        foreach ($table as $key => $item) {
           self::assertEquals(10, $key);
           self::assertEquals('5.4', $item);

           break;
        }

        $array = $record->setData(['name' => 1, 'price' => 2]);
        self::assertEquals(1, $record->getValue('name'));
        self::assertEquals(2, $record->getValue('price'));



    }

    public function testExceptions()
    {

        $record = new Record('testTable', 100, ['name' => 'john', 'price' => 4.3]);
        try {
            $record->getValue('fake');
        } catch (\Exception $e) {}
        self::assertInstanceOf(NotFoundException::class, $e);
        unset($e);

        try {
            $record->setValue('fake', 3);
        } catch (\Exception $e) {}
        self::assertInstanceOf(NotFoundException::class, $e);
        unset($e);

        try {
            $record->delete();
        } catch (\Exception $e) {}
        self::assertInstanceOf(DeleteFailException::class, $e);
        unset($e);

    }

}