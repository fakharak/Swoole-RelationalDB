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
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Registry\TableRegistry;

class ForeignKeyTest extends TestCase
{

    public function testForeignKey(): void
    {

        $userTable = TableRegistry::getInstance()->createTable('user', 255);
        $userTable->addColumn(new Column('name', ColumnType::string, 255));
        $userTable->addIndex(['name'], 1000, 256);
        $userTable->create();
        (new Record('user', 0, ['name' => 'john']))->persist();
        (new Record('user', 1, ['name' => 'paul']))->persist();
        (new Record('user', 2, ['name' => 'jack']))->persist();

        $projectTable = TableRegistry::getInstance()->createTable('project', 255);
        $projectTable->addColumn(new Column('name', ColumnType::string, 255));
        $projectTable->addColumn(new Column('ownerId', ColumnType::int, 16));
        $projectTable->addForeignKey('projectOwner', 'user', 'ownerId');
        $projectTable->create();
        (new Record('project', 0, ['name' => 'zero', 'ownerId' => 0]))->persist();
        (new Record('project', 1, ['name' => 'star wars', 'ownerId' => 0]))->persist();
        (new Record('project', 2, ['name' => 'cynderella', 'ownerId' => 1]))->persist();

        $record = $projectTable->getRecord(0);
        $records = $projectTable->getJoinedRecords('projectOwner', new RecordCollection([$projectTable->getName() => $record]), 'projectOwner');
        self::assertEquals('john', $records[0]['projectOwner']->getValue('name'));
        $record = $projectTable->getRecord(1);
        $records = $projectTable->getJoinedRecords('projectOwner', new RecordCollection([$projectTable->getName() => $record]), 'projectOwner');
        self::assertEquals('john', $records[0]['projectOwner']->getValue('name'));
        $record = $projectTable->getRecord(2);
        $records = $projectTable->getJoinedRecords('projectOwner', new RecordCollection([$projectTable->getName() => $record]), 'projectOwner');
        self::assertEquals('paul', $records[0]['projectOwner']->getValue('name'));

        $record = $userTable->getRecord(0);
        $records = $userTable->getJoinedRecords('projects', new RecordCollection([$userTable->getName() => $record]), 'projects');
        self::assertEquals('star wars', $records[1]['projects']->getValue('name'));

    }

}