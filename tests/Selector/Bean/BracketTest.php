<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Test\Selector\Bean;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Selector\Bean\Bracket;
use Small\SwooleDb\Selector\Bean\Condition;
use Small\SwooleDb\Selector\Bean\ConditionElement;
use Small\SwooleDb\Selector\Enum\ConditionElementType;
use Small\SwooleDb\Selector\Enum\ConditionOperator;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class BracketTest extends TestCase
{

    public function testAndOperations(): void
    {

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertFalse($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertFalse($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertFalse($bracket->validateBracket([]));

    }

    public function testOrOperations(): void
    {

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertFalse($bracket->validateBracket([]));

    }

    public function testMixedOperations(): void
    {

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = (new Bracket())
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertFalse($bracket->validateBracket([]));

    }

    public function testSubBracket(): void
    {

        $bracket = new Bracket();
        $bracket->firstBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        $bracket->andBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = new Bracket();
        $bracket->firstBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        $bracket->andBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->orCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

        $bracket = new Bracket();
        $bracket->firstBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        $bracket->orBracket()
            ->firstCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 1),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ))->andCondition(new Condition(
                new ConditionElement(ConditionElementType::const, 0),
                ConditionOperator::equal,
                new ConditionElement(ConditionElementType::const, 1),
            ));
        self::assertTrue($bracket->validateBracket([]));

    }

    public function testExceptions(): void
    {

        try {
            (new Bracket())
                ->firstCondition(new Condition(
                    new ConditionElement(ConditionElementType::const, 1),
                    ConditionOperator::equal,
                    new ConditionElement(ConditionElementType::const, 1),
                ))->firstCondition(new Condition(
                    new ConditionElement(ConditionElementType::const, 1),
                    ConditionOperator::equal,
                    new ConditionElement(ConditionElementType::const, 1),
                ));
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

        try {
            $bracket = new Bracket();
            $bracket->firstBracket();
            $bracket->firstBracket();
        } catch (\Exception $e) {}
        self::assertInstanceOf(SyntaxErrorException::class, $e);
        unset($e);

    }

}