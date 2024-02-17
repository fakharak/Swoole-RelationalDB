<?php

namespace Small\SwooleDb\Core\Enum;

use Small\SwooleDb\Exception\NotFoundException;

enum Operator
{

    case equal;
    case superior;
    case superiorOrEqual;
    case inferior;
    case inferiorOrEqual;

    public static function findByName(string $name): self
    {

        foreach (self::cases() as $operator) {
            if ($operator->name == $name) {
                return $operator;
            }
        }

        throw new NotFoundException('Operator ' . $name . ' not found');

    }

}