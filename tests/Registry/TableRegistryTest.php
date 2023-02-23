<?php

namespace Small\SwooleDb\Test\Registry;

use \PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Persistence\AsJsonFile;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\FileNotFoundException;
use Small\SwooleDb\Exception\TableAlreadyExists;
use Small\SwooleDb\Exception\TableNotExists;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\ParamRegistry;
use Small\SwooleDb\Registry\PersistenceRegistry;
use Small\SwooleDb\Registry\TableRegistry;

class TableRegistryTest extends TestCase
{

    public function testTableCreation()
    {

        TableRegistry::getInstance()->createTable('test', 125);

        self::assertInstanceOf(Table::class, TableRegistry::getInstance()->getTable('test'));

        try {
            TableRegistry::getInstance()->createTable('test', 52);
        } catch (\Exception $e) {}

        self::assertInstanceOf(TableAlreadyExists::class, $e);

    }

    public function testTableNotExists()
    {

        try {
            TableRegistry::getInstance()->getTable('fake');
        } catch (\Exception $e) {}

        self::assertInstanceOf(TableNotExists::class, $e);

        try {
            TableRegistry::getInstance()->destroy('fake');
        } catch (\Exception $e) {}

        self::assertInstanceOf(TableNotExists::class, $e);

    }

    public function testPersistence()
    {

        ParamRegistry::getInstance()->set(ParamType::varLibDir, '');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'tmp');

        $table = TableRegistry::getInstance()->createTable('testPersist', 125);
        $table->addColumn(
            new Column('test', ColumnType::string, 256),
        );
        $table->create();

        TableRegistry::getInstance()->persist('testPersist', PersistenceRegistry::DEFAULT);

        /** @var AsJsonFile $asJsonFile */
        $asJsonFile = PersistenceRegistry::getInstance()->getChannel(PersistenceRegistry::DEFAULT);

        $filename = $asJsonFile->getFilename('testPersist');

        $content = file_get_contents($filename);

        self::assertEquals(
            '{"name":"testPersist","columns":[{"name":"test","type":3,"size":256}],"rowMaxSize":125,"data":[]}',
            $content);

        TableRegistry::getInstance()->destroy('testPersist');

        TableRegistry::getInstance()->loadFromChannel('testPersist', PersistenceRegistry::DEFAULT);

        self::assertInstanceOf(Table::class, TableRegistry::getInstance()
            ->loadFromChannel('testPersist', PersistenceRegistry::DEFAULT)
        );

        try {
            TableRegistry::getInstance()->persist('fake', PersistenceRegistry::DEFAULT);
        } catch (\Exception $e) {}

        self::assertInstanceOf(TableNotExists::class, $e);

        try {
            TableRegistry::getInstance()->loadFromChannel('fake', PersistenceRegistry::DEFAULT);
        } catch (\Exception $e) {}

        self::assertInstanceOf(FileNotFoundException::class, $e);

    }

}