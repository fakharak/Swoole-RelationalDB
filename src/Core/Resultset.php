<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\Collection\Collection\Collection;
use Small\Collection\Contract\CheckValueInterface;
use Small\SwooleDb\Exception\RecordCollectionException;
use Small\SwooleDb\Selector\Bean\OrderByCollection;
use Small\SwooleDb\Selector\Bean\OrderByField;
use Small\SwooleDb\Selector\Enum\OrderBySens;

/**
 * @method RecordCollection current()
 * @method RecordCollection offsetGet(mixed $offset)
 */
class Resultset extends Collection
    implements CheckValueInterface
{
    #[\Override] public function checkValue(mixed $value): CheckValueInterface
    {

        if (!$value instanceof RecordCollection) {
            throw new RecordCollectionException(self::class . ' accept only ' . RecordCollection::class);
        }

        return $this;

    }

    public function orderBy(OrderByCollection $orderByCollection): self
    {

        usort($this->array, function (RecordCollection $a, RecordCollection $b)
            use($orderByCollection) {

            foreach ($orderByCollection as $i => $orderBy) {

                if (
                    $orderBy->sens == OrderBySens::ascending &&
                    $orderBy->translateGetValue($a) < $orderBy->translateGetValue($b)
                ) {
                    return -1;
                }

                if (
                    $orderBy->sens == OrderBySens::ascending &&
                    $orderBy->translateGetValue($a) > $orderBy->translateGetValue($b)
                ) {
                    return 1;
                }


                if (
                    $orderBy->sens == OrderBySens::descending &&
                    $orderBy->translateGetValue($a) > $orderBy->translateGetValue($b)
                ) {
                    return -1;
                }

                if (
                    $orderBy->sens == OrderBySens::descending &&
                    $orderBy->translateGetValue($a) < $orderBy->translateGetValue($b)
                ) {
                    return 1;
                }

            }

            return 0;

        });

        return $this;

    }

}