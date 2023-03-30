<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Selector\Bean;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class ConditionElementTest extends TestCase
{

    public function testAccessors()
    {

        $element = new ConditionElement(ConditionElementType::const,  2);
        self::assertEquals(ConditionElementType::const, $element->getType());
        self::assertNull($element->getTable());
        self::assertEquals(2, $element->getValue());

        $element = new ConditionElement(ConditionElementType::var, 'field', 'test');
        self::assertEquals(ConditionElementType::var, $element->getType());
        self::assertEquals('test', $element->getTable());
        self::assertEquals('field', $element->getValue());

        $element->setType(ConditionElementType::const);
        self::assertEquals(ConditionElementType::const, $element->getType());
        $element->setTable('table');
        self::assertEquals('table', $element->getTable());
        $element->setValue(7.5);
        self::assertEquals(7.5, $element->getValue());

    }

    public function testComputeValue()
    {

        $element = new ConditionElement(ConditionElementType::const, 2);
        self::assertEquals(2, $element->computeValue([]));

        $element = new ConditionElement(ConditionElementType::var, 'field', 'test');
        self::assertEquals(5, $element->computeValue(['test' => new Record('test', 0, ['field' => 5])]));

    }

    public function testElement()
    {

        try {
            new ConditionElement(ConditionElementType::var);
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            new ConditionElement(ConditionElementType::var, 2, 'test');
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

    }

}