<?php

namespace Small\SwooleDb\Test\Core\Persistence;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Persistence\AsJsonFile;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\FileNotFoundException;
use Small\SwooleDb\Exception\WrongFormatException;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\ParamRegistry;

class AsJsonFileTest extends TestCase
{

    public function testExceptions()
    {

        $asJsonFile = new AsJsonFile();
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(FileNotFoundException::class, $e);

        file_put_contents('/tmp/fail.json', 'not json');
        ParamRegistry::getInstance()->set(ParamType::varLibDir, '');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'tmp');
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);

        file_put_contents('/tmp/fail.json', json_encode([
            'test' => 'fail'
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('File /tmp/fail.json does\'nt contains rows size definition', $e->getMessage());

        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128'
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('File /tmp/fail.json does\'nt contains columns definition', $e->getMessage());

        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128',
            'columns' => [
                ['nama' => 'fail']
            ]
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('Column name of column #0 is missing', $e->getMessage());

        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128',
            'columns' => [
                ['name' => 'fail']
            ]
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('Column type of column #0 is missing', $e->getMessage());

        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128',
            'columns' => [
                ['name' => 'fail', 'type' => 1]
            ]
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('Column size of column #0 is missing', $e->getMessage());

        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128',
            'columns' => [
                ['name' => 'fail', 'type' => 1]
            ]
        ]));
        try {
            $asJsonFile->load("fail");
        } catch (\Exception $e) {}
        self::assertInstanceOf(WrongFormatException::class, $e);
        self::assertEquals('Column size of column #0 is missing', $e->getMessage());

        // Success
        file_put_contents('/tmp/fail.json', json_encode([
            'rowMaxSize' => '128',
            'columns' => [
                ['name' => 'fail', 'type' => Table::TYPE_STRING, 'size' => 12]
            ],
            'data' => [
                ['_key' => 0, 'fail' => 'string']
            ]
        ]));
        $table = $asJsonFile->load("fail");
        self::assertInstanceOf(Table::class, $table);

        // Persist
        $asJsonFile->persist('fail', $table);

        ParamRegistry::getInstance()->set(ParamType::varLibDir, '');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'tmp/data');
        $dir = $asJsonFile->getDataDirname();
        self::assertEquals('/tmp/data', $dir);

        ParamRegistry::getInstance()->set(ParamType::varLibDir, 'root');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'data/toto');
        try {
            $asJsonFile->getDataDirname();
        } catch (\Exception $e) {}
        self::assertInstanceOf(FileNotFoundException::class, $e);

    }

}