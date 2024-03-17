<?php

namespace Small\SwooleDb\Test\Core\Index;

use PHPUnit\Framework\TestCase;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\Index\Index;

class IndexTest extends TestCase
{

    public function testSignle()
    {

        $index = new Index('testSignle', 1000, 32);
        $report = [];
        foreach (range(1, 1000) as $value) {
            $index->insert($value, [$report[$value] = $value]);
        }

        $lesser = $index->getKeys(Operator::inferior, [50]);
        $greaterOrEqual = $index->getKeys(Operator::superiorOrEqual, [50]);

        self::assertEquals(1000, count($lesser) + count($greaterOrEqual));

        $lesserOrEqual = $index->getKeys(Operator::inferiorOrEqual, [50]);
        $greater = $index->getKeys(Operator::superior, [50]);

        self::assertEquals(1000, count($lesserOrEqual) + count($greater));

        foreach ($lesser as $item) {
            self::assertLessThan(50, $report[$item]);
        }

        foreach ($lesserOrEqual as $item) {
            self::assertLessThanOrEqual(50, $report[$item]);
        }

        foreach ($greater as $item) {
            self::assertGreaterThan(50, $report[$item]);
        }

        foreach ($greaterOrEqual as $item) {
            self::assertGreaterThanOrEqual(50, $report[$item]);
        }

    }

    public function testMulti()
    {

        $index = new Index('testMulti', 1000, 32);
        $report = [];
        foreach (range(1, 1000) as $value) {
            $index->insert($value, [$report[$value] = rand(1, 1000), $report2[$value] = rand(1, 1000)]);
        }

        $lesser = $index->getKeys(Operator::inferior, [50, 900]);
        $greaterOrEqual = $index->getKeys(Operator::superiorOrEqual, [50, 900]);

        self::assertEquals(1000, count($lesser) + count($greaterOrEqual));

        $lesserOrEqual = $index->getKeys(Operator::inferiorOrEqual, [50, 900]);
        $greater = $index->getKeys(Operator::superior, [50, 900]);

        self::assertEquals(1000, count($lesser) + count($greaterOrEqual));

        foreach ($lesser as $item) {

            $test = false;
            if ($report[$item] < 50) {
                $test = true;
            } else if ($report[$item] == 50 && $report2[$item] < 900) {
                $test = true;
            }

            self::assertTrue($test);

        }

        foreach ($lesserOrEqual as $item) {

            $test = false;
            if ($report[$item] < 50) {
                $test = true;
            } else if ($report[$item] == 50 && $report2[$item] <= 900) {
                $test = true;
            }

            self::assertTrue($test);

        }

        foreach ($greater as $item) {

            $test = false;
            if ($report[$item] > 50) {
                $test = true;
            } else if ($report[$item] == 50 && $report2[$item] > 900) {
                $test = true;
            }

            self::assertTrue($test);

        }

        foreach ($greaterOrEqual as $item) {

            $test = false;
            if ($report[$item] > 50) {
                $test = true;
            } else if ($report[$item] == 50 && $report2[$item] >= 900) {
                $test = true;
            }

            self::assertTrue($test);

        }

    }

}