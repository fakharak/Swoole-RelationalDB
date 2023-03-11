<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Registry;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\ParamRegistry;

class ParamRegistryTest extends TestCase
{

    public function testDefaultValues()
    {

        ParamRegistry::getInstance()->resetDefaults();

        self::assertEquals('/var/lib/small-swoole-db', ParamRegistry::getInstance()->get(ParamType::varLibDir));
        self::assertEquals('data', ParamRegistry::getInstance()->get(ParamType::dataDirName));

    }

    public function testSetter()
    {
        ParamRegistry::getInstance()->set(ParamType::varLibDir, 'test1');
        self::assertEquals('test1', ParamRegistry::getInstance()->get(ParamType::varLibDir));
    }

}