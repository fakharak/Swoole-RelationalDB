<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\Enum;

use Small\SwooleDb\Core\Table;

enum ColumnType: int
{

    case int = Table::TYPE_INT;
    case float = Table::TYPE_FLOAT;
    case string = Table::TYPE_STRING;

}