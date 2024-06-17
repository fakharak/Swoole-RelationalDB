<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

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
    case isNull = 'is null';
    case isNotNull = 'is not null';
    case exists = 'exists';
    case notExists = 'not exists';
    case in = 'in';
    case notIn = 'not in';

}