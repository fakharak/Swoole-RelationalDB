<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Selector\Join;

class ResultTree
{

    /**
     * @param Record[] $record
     * @param ResultTree[] $children
     */
    public function __construct(
        protected string $alias,
        protected Record $record,
        protected array $children = [],
    ) {}

    /**
     * @return ResultTree[]
     */
    public function getChildren(): array
    {

        return $this->children;

    }

    /**
     * @param string $from
     * @param string $alias
     * @param Join $join
     * @return $this
     */
    public function addChild(string $from, string $alias, Join $join): self
    {

        if ($from == $this->alias) {
            foreach ($join->get($this->record) as $record) {
                $this->children[] = new ResultTree($alias, $record);
            }
        } else {
            foreach ($this->children as $child) {
                $child->addChild($from, $alias, $join);
            }
        }

        return $this;

    }

    public function flatten(array $parent = []): array
    {
        
        $record = array_merge($parent, [$this->alias => $this->record]);
        if (count($this->children) == 0) {
            return count($parent) == 0 ? [$record] : $record;
        }

        $result = [];
        foreach ($this->children as $child) {
            $result[] = $child->flatten($record);
        }

        return $result;
    }

}