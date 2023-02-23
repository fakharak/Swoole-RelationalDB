<?php

namespace Small\SwooleDb\Core\Enum;

use Small\SwooleDb\Core\Table;

enum ColumnType: int
{

    case int = Table::TYPE_INT;
    case float = Table::TYPE_FLOAT;
    case string = Table::TYPE_STRING;

}