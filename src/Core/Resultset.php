<?php

namespace Small\SwooleDb\Core;

use Small\Collection\Collection\Collection;
use Small\Collection\Contract\CheckValueInterface;
use Small\SwooleDb\Exception\RecordCollectionException;

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

}