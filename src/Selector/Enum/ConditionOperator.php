<?php

namespace Small\SwooleDb\Selector\Enum;

enum ConditionOperator: string
{

    case equal = '=';
    case inferior = '<';
    case superior = '>';
    case inferiorOrEqual = '<=';
    case superiorOrEqual = '>=';
    case notEqual = '!=';
    case like = 'like';
    case notLike = 'not like';
    case regex = 'regex';
    case is = 'is';
    case isNot = 'is not';
    case exists = 'exists';
    case notExists = 'not exists';

}