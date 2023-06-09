<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector;

use Small\SwooleDb\Registry\TableRegistry;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Selector\Bean\Bracket;
use Small\SwooleDb\Selector\Bean\ResultTree;
use Small\SwooleDb\Selector\Exception\SyntaxErrorException;

class TableSelector
{

    /** @var Join[] */
    protected array $joins = [];
    protected Bracket $where;

    public function __construct(
        protected string $from,
        protected string|null $alias = null
    ) {

        if ($this->alias == null) {
            $this->alias = $this->from;
        }
        $this->where = new Bracket();

    }

    public function join(string $from, string $foreignKeyName, string $alias = null): self
    {

        if (array_key_exists($alias, $this->joins)) {
            throw new SyntaxErrorException('The join alias \'' . $alias . '\' already exists');
        }

        if (!array_key_exists($from, $this->joins) && $from != $this->from) {
            throw new SyntaxErrorException('The join from alias \'' . $from . '\' does not exists');
        }

        $this->joins[$from . '/' . $alias] = new Join($from, $foreignKeyName, $alias);

        return $this;

    }

    public function where(): Bracket
    {

        return $this->where;

    }

    /**
     * Execute query
     * @return Record[][]
     * @throws \Small\SwooleDb\Exception\TableNotExists
     */
    public function execute(): array
    {

        $fromTable = TableRegistry::getInstance()->getTable($this->from);

        $flatten = [];
        foreach ($fromTable as $key => $array) {
            $record = new Record($fromTable->getName(), $key, $array);
            $curTree = new ResultTree($this->alias, $record, []);
            foreach ($this->joins as $joinKey => $join) {
                list($from, $alias) = explode('/', $joinKey);
                $curTree->addChild($from, $alias, $join);
            }

            $flatten = array_merge($flatten, $curTree->flatten());
        }

        $result = [];
        foreach ($flatten as $alias => $record) {
            if ($this->where->validateBracket($record)) {
                $result[] = $record;
            }
        }

        return $result;

    }

}