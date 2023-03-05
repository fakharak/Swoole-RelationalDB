<?php

namespace Small\SwooleDb\Selector;

use Small\OrmSwoole\Storgae\TableRegistry;
use Small\SwooleDb\Core\Record;
use Small\SwooleDb\Selector\Bean\Bracket;

class TableSelector
{

    protected Bracket $where;

    public function __construct(protected string $from)
    {
        $this->where = new Bracket();
    }

    public function where(): Bracket
    {
        return $this->where;
    }

    /**
     * Execute query
     * @return Record[][]
     * @throws Exception\SyntaxErrorException
     */
    public function execute(): array
    {
        $fromTable = TableRegistry::getInstance()->get($this->from);

        $resultsets = [];
        foreach ($fromTable as $key => $record) {
            $record['_key'] = $key;
            $data[$this->from] = $record;
            if ($this->where()->validateBracket($data)) {
                $resultset[$this->from] = new Record($this->from, $key, $record);
                $resultsets= $resultset;
            }
        }

        return $resultsets;
    }

}