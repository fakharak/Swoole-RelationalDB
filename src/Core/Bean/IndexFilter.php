<?php

namespace Small\SwooleDb\Core\Bean;

use Small\SwooleDb\Core\Enum\Operator;

final readonly class IndexFilter
{

    public function __construct(
        public Operator $operator,
        public string $field,
        public string $value,
    ) {}

}