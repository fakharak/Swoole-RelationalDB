<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Selector\Bean;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class ConditionTest extends TestCase
{

    public function testGetters()
    {

        $condition = new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::equal,
            new ConditionElement(ConditionElementType::const, 3),
        );

        self::assertEquals(2, $condition->getLeftElement()->getValue());
        self::assertEquals(3, $condition->getRightElement()->getValue());
        self::assertEquals(ConditionOperator::equal, $condition->getOperator());

    }

    public function testEqual()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::equal,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 1),
            ConditionOperator::equal,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::equal,
            new ConditionElement(ConditionElementType::var, 'field2', "test2"),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 5]),
            'test2' => new Record('test2', 0, ['field2' => 5]),
        ])));

    }

    public function testNotEqual()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 1),
            ConditionOperator::notEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::notEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::notEqual,
            new ConditionElement(ConditionElementType::var, 'field2', "test2"),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 5]),
            'test2' => new Record('test2', 0, ['field2' => 2]),
        ])));

    }

    public function testInferior()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 1),
            ConditionOperator::inferior,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::inferior,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 3),
            ConditionOperator::inferior,
            new ConditionElement(ConditionElementType::const, 3),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::inferior,
            new ConditionElement(ConditionElementType::var, 'field2', 'test2'),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 1]),
            'test2' => new Record('test2', 0, ['field2' => 2]),
        ])));

    }

    public function testInferiorOrEqual()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 1),
            ConditionOperator::inferiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::inferiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 3),
            ConditionOperator::inferiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::inferiorOrEqual,
            new ConditionElement(ConditionElementType::var, 'field2', "test2"),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 1]),
            'test2' => new Record('test2', 0, ['field2' => 2]),
        ])));

    }

    public function testSuperior()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 3),
            ConditionOperator::superior,
            new ConditionElement(ConditionElementType::const,  2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::superior,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const,  1),
            ConditionOperator::superior,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::superior,
            new ConditionElement(ConditionElementType::var, 'field2', "test2"),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 2]),
            'test2' => new Record('test2', 0, ['field2' => 1]),
        ])));

    }

    public function testSuperiorOrEqual()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 3),
            ConditionOperator::superiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 2),
            ConditionOperator::superiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 1),
            ConditionOperator::superiorOrEqual,
            new ConditionElement(ConditionElementType::const, 2),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field1', 'test'),
            ConditionOperator::superiorOrEqual,
            new ConditionElement(ConditionElementType::var, 'field2', "test2"),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field1' => 3]),
            'test2' => new Record('test2', 0, ['field2' => 2]),
        ])));

    }

    public function testLike()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 'test'),
            ConditionOperator::like,
            new ConditionElement(ConditionElementType::const, '%'),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 'test'),
            ConditionOperator::like,
            new ConditionElement(ConditionElementType::const, 'te_t'),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 'test at night'),
            ConditionOperator::like,
            new ConditionElement(ConditionElementType::const, 'te_t%'),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 'tes'),
            ConditionOperator::like,
            new ConditionElement(ConditionElementType::const, 'te_t'),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 'tes at night'),
            ConditionOperator::like,
            new ConditionElement(ConditionElementType::const, 'te_t%'),
        ))->validateCondition(new RecordCollection([])));

    }

    public function testIs()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, null),
            ConditionOperator::isNull,
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, ''),
            ConditionOperator::isNull,
        ))->validateCondition(new RecordCollection([])));


        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::isNull,
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => null])
        ])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::isNull,
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => 0]),
        ])));

    }

    public function testRegex()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, '%bonjour%'),
            ConditionOperator::regex,
            new ConditionElement(ConditionElementType::const, '%[a-z]*%'),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, '%bonjour'),
            ConditionOperator::regex,
            new ConditionElement(ConditionElementType::const, '%[a-z]*%'),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::regex,
            new ConditionElement(ConditionElementType::const,'%[a-z]*%')
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => '%bonjour%']),
        ])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::regex,
            new ConditionElement(ConditionElementType::const,  '%[a-z]*%')
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => '%bonjourA%']),
        ])));

    }

    public function testExists()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, ['%bonjour%']),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, []),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, null),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection([
            'table' => new Record('table', 0, ['field' => 'val'])
        ])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection()));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::exists,
        ))->validateCondition(new RecordCollection([
            'table' => new Record('table', 0, ['field' => null])
        ])));

    }

    public function testNotExists()
    {

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, ['%bonjour%']),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, []),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, null),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([
            'table' => new Record('table', 0, ['field' => 'val'])
        ])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([
            'table' => new Record('table', 0, ['field' => null])
        ])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'table'),
            ConditionOperator::notExists,
        ))->validateCondition(new RecordCollection([
            'table' => new Record('table', 0, ['field' => null])
        ])));

    }

    public function testIn()
    {

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 'test'),
            ConditionOperator::in,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 'testa'),
            ConditionOperator::in,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::in,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => 'test'])
        ])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::in,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => 'testa'])
        ])));

    }

    public function testNotIn()
    {

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::const, 'test'),
            ConditionOperator::notIn,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::const, 'testa'),
            ConditionOperator::notIn,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([])));

        self::assertFalse((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::notIn,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => 'test'])
        ])));

        self::assertTrue((new Condition(
            new ConditionElement(ConditionElementType::var, 'field', 'test'),
            ConditionOperator::notIn,
            new ConditionElement(ConditionElementType::const, ['test', 'juice']),
        ))->validateCondition(new RecordCollection([
            'test' => new Record('test', 0, ['field' => 'testa'])
        ])));

    }

    public function testExceptions()
    {

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::isNull,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {
        }
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::isNotNull,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {
        }
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::exists,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::exists,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::notExists,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::in,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::notIn,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            (new Condition(
                new ConditionElement(ConditionElementType::const, null),
                ConditionOperator::notIn,
                new ConditionElement(ConditionElementType::const, 0),
            ))->validateCondition(new RecordCollection([]));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

    }

}