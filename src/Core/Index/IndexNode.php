<?php

namespace Small\SwooleDb\Core\Index;

use Small\SwooleDb\Core\Enum\Operator;

class IndexNode implements \JsonSerializable
{

    protected IndexData|null $data = null;

    /** @var IndexNode[] */
    protected array $childs = [];

    public function __construct(
        protected IndexNode|Index $parent,
    ) {

        if ($this->parent instanceof Index) {

            $this->parent->root = $this;

        }

    }

    /**
     * Insert a key for value
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function insert(string $key, mixed $value): self
    {

        if ($this->data === null) {
            $this->data = (new IndexData($value))->addKey($key);
        }

        if ($this->data->equal($value)) {

            $this->data->addKey($key);

        } else if ($this->data->superior($value)) {

            if (!array_key_exists(0, $this->childs)) {

                $this->childs[0] = new IndexNode($this);
                $this->childs[0]->data = (new IndexData($value))->addKey($key);

            } else {

                $this->childs[0]->insert($key, $value);

            }

        } else {

            if (!array_key_exists(1, $this->childs)) {

                $this->childs[1] = new IndexNode($this);
                $this->childs[1]->data = (new IndexData($value))->addKey($key);

            } else {

                $this->childs[1]->insert($key, $value);

            }
        }

        return $this;

    }

    /**
     * Search keys for value equal
     * @param mixed $value
     * @return string[]
     */
    public function searchEqual(mixed $value): array
    {

        if ($value == $this->data?->getValue()) {
            return $this->data?->getKeys() ?? [];
        } else if ($this->data?->inferior($value)) {
            if (!array_key_exists(0, $this->childs)) {
                return [];
            } else {
                return $this->childs[0]->searchEqual($value);
            }
        } else {
            if (!array_key_exists(1, $this->childs)) {
                return [];
            } else {
                return $this->childs[1]->searchEqual($value);
            }
        }

    }

    /**
     * @param Operator $operator
     * @param mixed $value
     * @return string[]
     */
    public function getKeys(Operator $operator, mixed $value): array
    {

        $result = [];

        switch ($operator) {

            case Operator::equal:
                $method = 'equal';
                break;

            case Operator::inferior:
                $method = 'inferior';
                $sens = 0;
                break;

            case Operator::inferiorOrEqual:
                $method = 'inferiorOrEqual';
                $sens = 0;
                break;

            case Operator::superior:
                $method = 'superior';
                $sens = 1;
                break;

            case Operator::superiorOrEqual:
                $method = 'superiorOrEqual';
                $sens = 1;
                break;

        }

        if ($this->data->$method($value)) {

            $result = $this->data?->getKeys() ?? [];

            if (array_key_exists(0, $this->childs)) {
                $result = array_merge($result, $this->childs[0]->getKeys($operator, $value));
            }

            if (array_key_exists(1, $this->childs)) {
                $result = array_merge($result, $this->childs[1]->getKeys($operator, $value));
            }

        } else {

            if (isset($sens) && array_key_exists($sens, $this->childs)) {
                $result = array_merge($result, $this->childs[$sens]->getKeys($operator, $value));
            }

        }

        return $result;

    }

    public function jsonSerialize(): mixed
    {

        return [
            'value' => $this->data?->getValue(),
            'keys' => $this->data?->getKeys(),
            'lesser' => array_key_exists(0, $this->childs) ? $this->childs[0]->jsonSerialize() : null,
            'greater' => array_key_exists(1, $this->childs) ? $this->childs[1]->jsonSerialize() : null,
        ];

    }

}