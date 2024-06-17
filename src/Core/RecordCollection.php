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

/**
 * @method Record current()
 * @method Record offsetGet(mixed $offset)
 */
class   RecordCollection extends Collection
    implements CheckValueInterface
{
    #[\Override] public function checkValue(mixed $value): CheckValueInterface
    {

        if (!$value instanceof Record) {
            throw new RecordCollectionException(self::class . ' accept only ' . Record::class);
        }

        return $this;

    }

}