<?php

namespace Small\SwooleDb\Core\Index;

use _PHPStan_cc8d35ffb\Symfony\Component\Console\Exception\LogicException;
use Small\SwooleDb\Core\Enum\Operator;
use Small\SwooleDb\Core\Index\Enum\IndexNodeFrom;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\IndexException;

final class IndexNode implements \JsonSerializable
{

    protected IndexData|null $data = null;
    protected int $key = 0;
    protected int $keyLeft = 0;
    protected int $keyRight = 0;

    /** @var IndexNode[] */
    protected array $childs = [];

    public function __construct(
        protected IndexNode|Index $parent,
        protected Table $indexTable,
        protected IndexNodeFrom $indexNodeFrom = IndexNodeFrom::root,
    ) {}

    public function getLeft(): IndexNode|null
    {

        if (!array_key_exists(0, $this->childs) && $this->keyLeft != 0) {

            $this->childs[0] = new IndexNode($this, $this->indexTable, IndexNodeFrom::left);
            $this->childs[0]->load($this->keyLeft);

        }

        return $this->childs[0];

    }

    public function getRight(): IndexNode|null
    {

        if (!array_key_exists(1, $this->childs) && $this->keyRight != 0) {

            $this->childs[1] = new IndexNode($this, $this->indexTable, IndexNodeFrom::right);
            $this->childs[1]->load($this->keyRight);

        }

        return $this->childs[1];

    }

    /**
     * Insert a key for value
     * @param string $key
     * @param (int|string|float|null)[] $values
     * @return $this
     */
    public function insert(string $key, array $values): self
    {

        if ($this->data === null) {
            $this->data = (new IndexData($values))->addKey($key);
        }

        if ($this->data->equal($values)) {

            $this->data->addKey($key);
            $this->persist();

        } else if ($this->data->superior($values)) {

            if ($this->keyLeft === 0) {

                $this->childs[0] = new IndexNode($this, $this->indexTable, IndexNodeFrom::left);
                $this->childs[0]->data = (new IndexData($values))->addKey($key);
                $this->persist();
                $this->childs[0]->persist();
            } else {

                $this->getLeft()?->insert($key, $values);

            }

        } else {

            if ($this->keyRight === 0) {

                $this->childs[1] = new IndexNode($this, $this->indexTable, IndexNodeFrom::right);
                $this->childs[1]->data = (new IndexData($values))->addKey($key);
                $this->persist();
                $this->childs[1]->persist();

            } else {

                $this->getRight()?->insert($key, $values);

            }
        }

        return $this;

    }

    /**
     * Load node
     * @param int $key
     * @return $this
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function load(int $key): self
    {

        $record = $this->indexTable->getRecord((string)$key);

        $this->key = $key;

        $keyLeft = $record->getValue('keyLeft');
        if (is_string($keyLeft) || is_int($keyLeft)) {
            $keyLeft = (int)$keyLeft;
        } else {
            throw new LogicException('Bad type');
        }

        $keyRight = $record->getValue('keyRight');
        if (is_string($keyRight) || is_int($keyRight)) {
            $keyRight = (int)$keyRight;
        } else {
            throw new LogicException('Bad type');
        }

        if (!is_string($rawData = $record->getValue('data'))) {
            throw new \LogicException('Bad json');
        }
        $data = json_decode($rawData, true);
        if (!is_array($data)) {
            throw new \LogicException('Bad json');
        }

        if (!is_string($rawTableKeys = $record->getValue('tableKeys'))) {
            throw new \LogicException('Bad json');
        }
        $tableKeys = json_decode($rawTableKeys, true);
        if (!is_array($tableKeys)) {
            throw new \LogicException('Bad json');
        }

        $this->keyLeft = $keyLeft;
        $this->keyRight = $keyRight;
        $this->data = new IndexData($data);

        /** @var string[] $tableKeys */
        foreach ($tableKeys as $tableKey) {
            $this->data->addKey($tableKey);
        }

        return $this;

    }

    protected function persist(): self
    {

        if ($this->key == 0) {
            $this->key = $this->indexTable->count() + 1;
        }

        if (!is_string($data = json_encode($this->data?->getValues()))) {
            throw new \LogicException('Bad data');
        }

        if (!is_string($tableKeys = json_encode($this->data?->getKeys()))) {
            throw new \LogicException('Bad data');
        }

        $this->indexTable->set((string)$this->key, [
            'keyLeft' => $this->keyLeft,
            'keyRight' => $this->keyRight,
            'data' => $data,
            'tableKeys' => $tableKeys
        ]);

        if ($this->parent instanceof IndexNode) {
            switch ($this->indexNodeFrom) {
                case IndexNodeFrom::left:
                    if ($this->parent->keyLeft === 0) {
                        $this->parent->keyLeft = $this->key;
                        $this->parent->persist();
                    }
                    break;

                case IndexNodeFrom::right:
                    if ($this->parent->keyRight === 0) {
                        $this->parent->keyRight = $this->key;
                        $this->parent->persist();
                    }
                    break;
            }
        }

        return $this;

    }

    /**
     * Search keys for value equal
     * @param (int|float|string|null)[] $values
     * @return string[]
     * @throws IndexException
     */
    public function searchEqual(array $values): array
    {

        return $this->searchNode($values)?->data?->getKeys() ?? [];

    }

    /**
     * @param string $key
     * @param (int|float|string|null)[] $values
     * @return $this
     * @throws IndexException
     */
    public function removeKey(string $key, array $values): self
    {

        $node = $this->searchNode($values);

        if ($node != null) {

            $node->data?->removeKey($key);
            $node->persist();

        }

        return $this;

    }

    /**
     * Search node for value equal
     * @param (int|float|string|null)[] $values
     * @return IndexNode
     * @throws IndexException
     */
    protected function searchNode(array $values): IndexNode|null
    {

        $found = true;
        foreach ($values as $key => $item) {

            if (!array_key_exists($key, $this->data?->getValues() ?? [])) {
                $found = false;
            }

            if ($item != ($this->data?->getValues() ?? [])[$key]) {
                $found = false;
            }

        }

        if ($found) {
            return $this;
        }

        if ($this->data?->inferior($values)) {
            if ($this->getRight() === null) {
                return null;
            } else {
                return $this->getRight()->searchNode($values);
            }

        } else {

            if ($this->getLeft() === null) {
                return null;
            } else {
                return $this->getLeft()->searchNode($values);
            }

        }

    }

    /**
     * Get keys for operation
     * @param Operator $operator
     * @param (int|float|string|null)[] $values
     * @return array|string[]
     * @throws IndexException
     */
    public function getKeys(Operator $operator, array $values): array
    {

        $result = [];

        switch ($operator) {

            case Operator::equal:
                return $this->searchEqual($values);

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

        if ($this->data->$method($values)) {

            $result = $this->data?->getKeys() ?? [];

            if ($this->getLeft() !== null) {
                $result = array_merge($result, $this->getLeft()->getKeys($operator, $values));
            }

            if ($this->getRight() !== null) {
                $result = array_merge($result, $this->getRight()->getKeys($operator, $values));
            }

        } else {

            $childs[0] = $this->getLeft();
            $childs[1] = $this->getRight();
            if ($childs[$sens] !== null) {
                $result = array_merge($result, $childs[$sens]->getKeys($operator, $values));
            }

        }

        return $result;

    }

    public function jsonSerialize(): mixed
    {

        return [
            'value' => $this->data?->getValues(),
            'keys' => $this->data?->getKeys(),
            'lesser' => $this->getLeft()
                ?->jsonSerialize(),
            'greater' => $this->getRight()
                ?->jsonSerialize(),
        ];

    }

}