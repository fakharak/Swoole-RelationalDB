<?php

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