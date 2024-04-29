<?php

namespace Small\SwooleDb\Core\Contract;

interface IdGeneratorInterface
{

    public function generate(): string;

}