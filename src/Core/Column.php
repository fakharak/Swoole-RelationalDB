<?php

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Exception\MalformedTable;

class Column
{

    const KEY_COL_NAME = '_key';

    const FORBIDDEN_NAMES = [
        self::KEY_COL_NAME,
    ];

    public function __construct(protected string $name, protected ColumnType $type, protected int $size)
    {
        if (in_array($this->name, static::FORBIDDEN_NAMES)) {
            throw new MalformedTable('The column name \'' . $this->name . '\' is forbidden');
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ColumnType
     */
    public function getType(): ColumnType
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

}