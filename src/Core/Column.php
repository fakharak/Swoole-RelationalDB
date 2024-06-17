<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core;

use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Exception\MalformedTable;

class Column
{

    const KEY_COL_NAME = '_key';

    const MAX_FIELD_NAME_SIZE = 256;

    const FORBIDDEN_NAMES = [
        self::KEY_COL_NAME,
    ];

    public function __construct(
        protected readonly string $name,
        protected readonly ColumnType $type,
        protected readonly int $size = 0,
        protected readonly mixed $nullValue = -1,
    )
    {

        if (strlen($this->name) > self::MAX_FIELD_NAME_SIZE) {
            throw new MalformedTable('The column name \'' . $this->name . '\' exceed max chars lenght for field name (' . self::MAX_FIELD_NAME_SIZE . ' chars)');
        }

        if (in_array($this->name, static::FORBIDDEN_NAMES)) {
            throw new MalformedTable('The column name \'' . $this->name . '\' is forbidden');
        }

        if ($this->type != ColumnType::float && $this->size === 0) {
            throw new MalformedTable('Missing size param for ' . $this->type->name . ' type, creating ' . $name . ' column');
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
    public function getSize(): int|null
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getNullValue(): mixed
    {

        return $this->nullValue;

    }

}