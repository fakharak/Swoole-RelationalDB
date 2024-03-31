<?php

namespace Small\SwooleDb\Selector\Bean;

use Small\Collection\Collection\Collection;
use Small\Collection\Contract\CheckValueInterface;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

/**
 * @method OrderByField offsetGet(mixed $offset)
 * @method OrderByField current()
 */
class OrderByCollection extends Collection
    implements CheckValueInterface
{
    #[\Override] public function checkValue(mixed $value): CheckValueInterface
    {

        if (!$value instanceof OrderByField) {
            throw new SyntaxErrorException(self::class . ' accept only ' . OrderByField::class);
        }

        return $this;

    }

}