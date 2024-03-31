<?php

namespace Small\SwooleDb\Selector\Bean;

use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\RecordCollection;
use Small\SwooleDb\Selector\Enum\OrderBySens;

readonly class OrderByField
{

    public function __construct(
        public string $alias,
        public string $field,
        public OrderBySens $sens = OrderBySens::ascending,
    ) {}

    /**
     * @param RecordCollection $recordCollection
     * @return float|int|string|null
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function translateGetValue(RecordCollection $recordCollection): float|int|string|null
    {

        foreach ($recordCollection as $alias => $record) {

            if ($alias == $this->alias) {
                return $this->field == Column::KEY_COL_NAME ? $record->getKey() : $record->getValue($this->field);
            }

        }

        throw new \LogicException(
            'Table alias or field not found (' . $this->alias . ' - ' . $this->field . ')'
        );

    }

}