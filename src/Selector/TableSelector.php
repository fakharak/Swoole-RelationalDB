<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Selector;

use Small\SwooleDb\Core\Bean\IndexFilter;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Core\Resultset;
use Small\SwooleDb\Registry\TableRegistry;
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

    /**
     * @return IndexFilter[][]
     */
    protected function getOptimisation(): array
    {

        return $this->where->getOptimisations();

    }

    public function join(string $from, string $foreignKeyName, string $alias = null): self
    {

        if ($alias === null) {
            $alias = $from;
        }

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
     * @return Resultset
     * @throws \Small\SwooleDb\Exception\TableNotExists
     */
    public function execute(): Resultset
    {

        $fromTable = TableRegistry::getInstance()->getTable($this->from);

        if ($fromFilters = $this->getOptimisation()[$fromTable->getName()]) {
            $records = $fromTable->filterWithIndex($fromFilters);
        } else {
            $records = $fromTable;
        }

        $flatten = [];
        foreach ($records as $record) {

            if ($this->alias === null) {
                throw new \LogicException('Alias can\'t be null at this point');
            }

            $curTree = new ResultTree($this->alias, $record, []);
            foreach ($this->joins as $joinKey => $join) {
                list($from, $alias) = explode('/', $joinKey);
                $curTree->addChild($from, $alias, $join);
            }

            /** @phpstan-ignore-next-line */
            $flatten = array_merge($flatten, $curTree->flatten());
        }

        $result = new Resultset();
        foreach ($flatten as $alias => $record) {
            if ($this->where->validateBracket($record)) {
                if (is_array($record)) {
                    $result[] = new RecordCollection($record);
                } else {
                    $result[] = new RecordCollection([$record]);
                }
            }
        }

        return $result;

    }

}