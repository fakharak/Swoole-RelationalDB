<?php

namespace Small\SwooleDb\Test\Registry;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Persistence\AsJsonFile;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\ParamRegistry;
use Small\SwooleDb\Registry\PersistenceRegistry;

class PersistenceRegistryTest extends TestCase
{

    public function testSetters()
    {

        ParamRegistry::getInstance()->set(ParamType::varLibDir, '');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'tmp');

        PersistenceRegistry::getInstance()->setDefaultChannel(
            new AsJsonFile(),
        );

        /** @var AsJsonFile $channel */
        $channel = PersistenceRegistry::getInstance()->getChannel(PersistenceRegistry::DEFAULT);

        self::assertEquals($channel->getDataDirname(), '/tmp');

        ParamRegistry::getInstance()->set(ParamType::varLibDir, '');
        ParamRegistry::getInstance()->set(ParamType::dataDirName, 'tmp');

        PersistenceRegistry::getInstance()->setChannel(
            'test',
            new AsJsonFile()
        );

        $channel = PersistenceRegistry::getInstance()->getChannel('test');

        self::assertEquals($channel->getDataDirname(), '/tmp');

    }

    public function testExceptions()
    {

        try {
            PersistenceRegistry::getInstance()->getChannel('testFail');
        } catch (\Exception $e) {}

        self::assertInstanceOf(NotFoundException::class, $e);

    }

}